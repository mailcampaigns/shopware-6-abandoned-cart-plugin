<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart;

use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Service\ShopwareVersionHelper;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
final class CartRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService,
        private readonly ShopwareVersionHelper $versionHelper,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * Finds and returns an array of `cart` records that are considered "abandoned" and meet specific criteria.
     * 
     * This method performs the following steps:
     * 1. Retrieves cart records that are not marked as abandoned OR have been updated after being marked as abandoned. Depending on the value of the $retrieveUpdated parameter.
     * 2. Filters carts to include only those with a customer ID.
     * 3. Excludes carts marked for recalculation.
     * 4. Adds customer ID, total price, and line item count to each cart.
     * 5. Removes carts associated with inactive customers.
     * 6. Removes carts for customers who have placed an order after the cart was created.
     * 
     * @param bool $retrieveUpdated Optional parameter to specify whether to retrieve updated abandoned carts.
     *                              If true, retrieves updated abandoned carts. If false, retrieves new abandoned carts.
     *                              Default is false.
     * @return array An array of abandoned cart records with additional details.
     * @throws Exception
     */
    public function findAbandonedCartsWithCriteria(bool $retrieveUpdated = false): array
    {

        $abandonedCartTokens = $this->getAbandonedCartTokens();
        if (count($abandonedCartTokens) === 0) {
            return [];
        }

        $qb = $this->connection->createQueryBuilder();

        $field = $this->payloadExists() ? 'payload' : 'cart';
        if($this->versionHelper->getMajorMinorShopwareVersion() === '6.5') {
            $qb->select("c.token, c.$field AS payload, c.created_at, c.updated_at AS c_updated_at, ac.updated_at AS ac_updated_at")
                ->addSelect('LOWER(HEX(c.customer_id)) AS customer_id')
                ->addSelect('c.compressed')
                ->addSelect('c.price')
                ->addSelect('c.line_item_count')
                ->from('cart', 'c')
                ->leftJoin('c', 'abandoned_cart', 'ac', 'c.token = ac.cart_token')
                ->where($qb->expr()->in('c.token', ':tokens'))
                ->setParameter('tokens', $abandonedCartTokens, ArrayParameterType::STRING)
                ->setMaxResults(100);

            if (!$retrieveUpdated) { // Not yet marked as abandoned
                $qb->andWhere($qb->expr()->isNull('ac.id'));
            } else{ // Updated after marked as abandoned
                $qb->andWhere($qb->expr()->gt('c.updated_at', 'c.created_at'));
                $qb->andWhere(
                    $qb->expr()->or(
                        $qb->expr()->isNull('ac.updated_at'),
                        $qb->expr()->gt('c.updated_at', 'ac.updated_at')
                    )
                );
            }
        } else if($this->versionHelper->getMajorMinorShopwareVersion() === '6.6') {
            $qb->select("c.token, c.$field AS payload, c.created_at, ac.updated_at")
                ->addSelect('c.compressed')
                ->from('cart', 'c')
                ->leftJoin('c', 'abandoned_cart', 'ac', 'c.token = ac.cart_token')
                ->where($qb->expr()->in('c.token', ':tokens'))
                ->setParameter('tokens', $abandonedCartTokens, ArrayParameterType::STRING)
                ->setMaxResults(100);

            if (!$retrieveUpdated) { // Not yet marked as abandoned
                $qb->andWhere($qb->expr()->isNull('ac.id'));
            } else{ // Updated after marked as abandoned
                $qb->andWhere($qb->expr()->gt('c.created_at', 'ac.created_at'));
                $qb->andWhere($qb->expr()->isNull('ac.updated_at'));
                $qb->orWhere($qb->expr()->gt('c.created_at', 'ac.updated_at'));
            }
        }

        $data = $qb->executeQuery()->fetchAllAssociative();

        foreach($data as $key => $row) {
            try {
                $cartObj = !empty($row['compressed']) ? CacheValueCompressor::uncompress($row['payload']) : unserialize((string) $row['payload']);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to unserialize cart payload.', ['exception' => $e]);
                $cartObj = null;
            }

            if (!$cartObj instanceof \Shopware\Core\Checkout\Cart\Cart) {
                $this->logger->error('Invalid cart object.', ['cart' => $row['payload']]);
                unset($data[$key]);
                continue;
            }

            // Extract customerId, price and item count from cart payload for 6.6
            if($this->versionHelper->getMajorMinorShopwareVersion() === '6.6') {
                $customerId = $this->extractCustomerIdFromCart($cartObj);
                if (!$customerId) {
                    unset($data[$key]);
                    continue;
                }

                $data[$key]['customer_id'] = $customerId;
                // Set price from cart object
                $data[$key]['price'] = $this->extractPriceFromCart($cartObj);
                // Set line item count from cart object
                $data[$key]['line_item_count'] = $this->extractLineItemCountFromCart($cartObj);

                // Check if customer is active by querying the database
                $qb_customer = $this->connection->createQueryBuilder();
                $customerActive = $qb_customer->select('c.active')
                    ->from('customer', 'c')
                    ->where($qb_customer->expr()->eq('c.id', ':customerId'))
                    ->setParameter('customerId', hex2bin($customerId))
                    ->executeQuery()
                    ->fetchOne();
                    
                if (!$customerActive) {
                    unset($data[$key]);
                    continue;
                }
            }

            // Remove carts that are marked as recalculated since they can be considered as garbage
            if($cartObj->getBehavior()->isRecalculation()) {
                unset($data[$key]);
                continue;
            }

            if($retrieveUpdated) {
                /** @var ModificationTimeStruct|null $modificationTime */
                $modificationTime = $cartObj->getExtension(ModificationTimeStruct::CART_EXTENSION_NAME);

                $data[$key]['modified_at'] = $modificationTime?->getModifiedAt()?->format(Defaults::STORAGE_DATE_TIME_FORMAT)
                    ?? $data[$key]['c_updated_at']
                    ?? $data[$key]['created_at'];
            }

            // Remove carts of customers that have placed an order after the cart was created
            $qb = $this->connection->createQueryBuilder();
            $qb->select('oc.id')
                ->from('order_customer', 'oc')
                ->leftJoin('oc', 'customer', 'c', 'oc.customer_id = c.id')
                ->where($qb->expr()->eq('oc.customer_id', ':customerId'))
                ->andWhere($qb->expr()->gte('oc.created_at', ':cartCreatedAt'))
                ->setParameter('customerId', $data[$key]['customer_id'])
                ->setParameter('cartCreatedAt', $data[$key]['created_at']);

            $result = $qb->executeQuery()->fetchOne();
            if($result !== false) {
                unset($data[$key]);
                continue;
            }
        }

        $this->logger->debug(count($data) . ' abandoned carts found.', $qb->getParameters());

        return $data;
    }

    /**
     * Obtains tokens of carts that are considered abandoned.
     *
     * This function constructs an SQL query that selects the most recent cart token
     * for each customer whose cart has been abandoned. A cart is considered abandoned
     * if it was created before a certain time threshold, which is determined by the
     * 'MailCampaignsAbandonedCart.config.markAbandonedAfter' configuration setting.
     *
     * @return array<string> Returns an array of cart tokens that are considered abandoned.
     */
    private function getAbandonedCartTokens(): array
    {
        $considerAbandonedAfter = (new DateTime())->modify(sprintf(
            '-%d seconds',
            $this->systemConfigService->get('MailCampaignsAbandonedCart.config.markAbandonedAfter')
        ));

        if($this->versionHelper->getMajorMinorShopwareVersion() === '6.5') {
            $sql = <<<SQL
                SELECT
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(cart.`token` ORDER BY IFNULL(cart.updated_at, cart.created_at) DESC),
                        ',',
                        1
                    ) AS `token`
                FROM cart
                INNER JOIN customer ON customer.id = cart.customer_id AND customer.active = 1
                WHERE IFNULL(cart.updated_at, cart.created_at) < '{$considerAbandonedAfter->format('Y-m-d H:i:s.v')}'
                AND cart.customer_id IS NOT NULL
                GROUP BY cart.customer_id
            SQL;
        }
        else if ($this->versionHelper->getMajorMinorShopwareVersion() === '6.6') {
            $sql = <<<SQL
                SELECT
                    cart.`token`
                FROM cart
                WHERE cart.created_at < '{$considerAbandonedAfter->format('Y-m-d H:i:s.v')}'
            SQL;
        }
        else {
            throw new \RuntimeException('Unsupported Shopware version ' . $this->versionHelper->getMajorMinorShopwareVersion());
        }

        $statement = $this->connection->prepare($sql);

        return $statement->executeQuery()->fetchFirstColumn();
    }

    /**
     * In some Shopware installations, the `cart` table may not have a `payload` column, but a `cart` column instead.
     * This method checks if the `payload` column exists in the `cart` table.
     * @return bool True if the `payload` column exists, false otherwise.
     */
    private function payloadExists(): bool
    {
        $statement = $this->connection->prepare(<<<SQL
            SHOW COLUMNS FROM cart;
        SQL);

        return in_array('payload', array_column(
            $statement->executeQuery()->fetchAllAssociative(),
            'Field'
        ));
    }

    /**
     * Extracts customer ID from cart object for Shopware 6.6.
     * In Shopware 6.6, customer ID is stored in the shipping location address.
     * 
     * @param \Shopware\Core\Checkout\Cart\Cart $cartObj
     * @return string|null
     */
    private function extractCustomerIdFromCart(\Shopware\Core\Checkout\Cart\Cart $cartObj): ?string
    {
        try {
            // Try to get customer ID from deliveries -> shipping location -> address -> customer ID
            $deliveries = $cartObj->getDeliveries();
            if ($deliveries && $deliveries->count() > 0) {
                $delivery = $deliveries->first();
                $shippingLocation = $delivery?->getLocation();
                $address = $shippingLocation?->getAddress();
                
                if ($address && method_exists($address, 'getCustomerId')) {
                    $customerId = $address->getCustomerId();
                    if ($customerId) {
                        return $customerId;
                    }
                }
            }

            // Alternative approach: try to get from extensions or other cart properties
            $extensions = $cartObj->getExtensions();
            foreach ($extensions as $extension) {
                if (method_exists($extension, 'getCustomerId')) {
                    $customerId = $extension->getCustomerId();
                    if ($customerId) {
                        return $customerId;
                    }
                }
            }

            // Log debug information about the cart structure
            $this->logger->debug('Unable to extract customer ID from cart object', [
                'token' => $cartObj->getToken(),
                'deliveries_count' => $deliveries ? $deliveries->count() : 0,
                'extensions' => array_keys($extensions),
            ]);

            return null;
        } catch (\Throwable $e) {
            $this->logger->error('Error extracting customer ID from cart object', [
                'exception' => $e->getMessage(),
                'token' => $cartObj->getToken(),
            ]);
            return null;
        }
    }

    /**
     * Extracts total price from cart object for Shopware 6.6.
     * 
     * @param \Shopware\Core\Checkout\Cart\Cart $cartObj
     * @return float|null
     */
    private function extractPriceFromCart(\Shopware\Core\Checkout\Cart\Cart $cartObj): ?float
    {
        try {
            // Primary method: get price from cart price object
            $price = $cartObj->getPrice();
            if ($price && method_exists($price, 'getTotalPrice')) {
                $totalPrice = $price->getTotalPrice();
                if ($totalPrice !== null) {
                    return $totalPrice;
                }
            }

            // Alternative method: calculate from line items
            $lineItems = $cartObj->getLineItems();
            if ($lineItems && method_exists($lineItems, 'getElements')) {
                $totalPrice = 0.0;
                foreach ($lineItems->getElements() as $lineItem) {
                    if (method_exists($lineItem, 'getPrice')) {
                        $itemPrice = $lineItem->getPrice();
                        if ($itemPrice && method_exists($itemPrice, 'getTotalPrice')) {
                            $totalPrice += $itemPrice->getTotalPrice();
                        }
                    }
                }
                if ($totalPrice > 0) {
                    return $totalPrice;
                }
            }

            $this->logger->debug('Unable to extract price from cart object', [
                'token' => $cartObj->getToken(),
                'has_price' => $price !== null,
                'has_line_items' => $lineItems !== null,
            ]);

            return null;
        } catch (\Throwable $e) {
            $this->logger->error('Error extracting price from cart object', [
                'exception' => $e->getMessage(),
                'token' => $cartObj->getToken(),
            ]);
            return null;
        }
    }

    /**
     * Extracts line item count from cart object for Shopware 6.6.
     * 
     * @param \Shopware\Core\Checkout\Cart\Cart $cartObj
     * @return int
     */
    private function extractLineItemCountFromCart(\Shopware\Core\Checkout\Cart\Cart $cartObj): int
    {
        try {
            // Primary method: get line items collection and count
            $lineItems = $cartObj->getLineItems();
            if ($lineItems) {
                // Try different methods to get count
                if (method_exists($lineItems, 'count')) {
                    return $lineItems->count();
                }
                
                if (method_exists($lineItems, 'getElements')) {
                    $elements = $lineItems->getElements();
                    if (is_array($elements) || $elements instanceof \Countable) {
                        return count($elements);
                    }
                }

                // Fallback: iterate through items
                $count = 0;
                foreach ($lineItems as $item) {
                    $count++;
                }
                return $count;
            }

            $this->logger->debug('Unable to extract line item count from cart object', [
                'token' => $cartObj->getToken(),
                'has_line_items' => $lineItems !== null,
            ]);

            return 0;
        } catch (\Throwable $e) {
            $this->logger->error('Error extracting line item count from cart object', [
                'exception' => $e->getMessage(),
                'token' => $cartObj->getToken(),
            ]);
            return 0;
        }
    }
}
