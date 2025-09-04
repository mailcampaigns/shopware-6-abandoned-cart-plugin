<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart\Handler;

use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\Cart\ModificationTimeStruct;
use Shopware\Core\Defaults;

/**
 * Cart repository handler for Shopware 6.6
 */
class Shopware66CartRepositoryHandler implements CartRepositoryHandlerInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {
    }

    public function buildAbandonedCartsQuery(array $abandonedCartTokens, bool $retrieveUpdated): array
    {
        $qb = $this->connection->createQueryBuilder();

        $field = $this->payloadExists() ? 'payload' : 'cart';
        
        $qb->select("c.token, c.$field AS payload, c.created_at, ac.updated_at")
            ->addSelect('c.compressed')
            ->from('cart', 'c')
            ->leftJoin('c', 'abandoned_cart', 'ac', 'c.token = ac.cart_token')
            ->where($qb->expr()->in('c.token', ':tokens'))
            ->setParameter('tokens', $abandonedCartTokens, ArrayParameterType::STRING)
            ->setMaxResults(100);

        if (!$retrieveUpdated) { // Not yet marked as abandoned
            $qb->andWhere($qb->expr()->isNull('ac.id'));
        } else { // Updated after marked as abandoned
            $qb->andWhere($qb->expr()->gt('c.created_at', 'ac.created_at'));
            $qb->andWhere($qb->expr()->isNull('ac.updated_at'));
            $qb->orWhere($qb->expr()->gt('c.created_at', 'ac.updated_at'));
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function getAbandonedCartTokensQuery(DateTime $considerAbandonedAfter): string
    {
        return <<<SQL
            SELECT
                cart.`token`
            FROM cart
            WHERE cart.created_at < '{$considerAbandonedAfter->format('Y-m-d H:i:s.v')}'
        SQL;
    }

    public function processCartRow(array $row, \Shopware\Core\Checkout\Cart\Cart $cartObj, bool $retrieveUpdated): ?array
    {
        // Extract customerId, price and item count from cart payload for 6.6
        $customerId = $this->extractCustomerIdFromCart($cartObj);
        if (!$customerId) {
            return null; // Filter out this cart
        }

        $row['customer_id'] = $customerId;
        $row['price'] = $this->extractPriceFromCart($cartObj);
        $row['line_item_count'] = $this->extractLineItemCountFromCart($cartObj);

        // Check if customer is active by querying the database
        if (!$this->isCustomerActive($customerId)) {
            return null; // Filter out this cart
        }

        if ($retrieveUpdated) {
            /** @var ModificationTimeStruct|null $modificationTime */
            $modificationTime = $cartObj->getExtension(ModificationTimeStruct::CART_EXTENSION_NAME);

            $row['modified_at'] = $modificationTime?->getModifiedAt()?->format(Defaults::STORAGE_DATE_TIME_FORMAT)
                ?? $row['c_updated_at']
                ?? $row['created_at'];
        }

        return $row;
    }

    public function getSupportedVersion(): string
    {
        return '6.6-6.7'; // Updated to support both 6.6 and 6.7
    }

    /**
     * In some Shopware installations, the `cart` table may not have a `payload` column, but a `cart` column instead.
     * This method checks if the `payload` column exists in the `cart` table.
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

    /**
     * Check if customer is active by querying the database
     */
    private function isCustomerActive(string $customerId): bool
    {
        try {
            $qb = $this->connection->createQueryBuilder();
            $customerActive = $qb->select('c.active')
                ->from('customer', 'c')
                ->where($qb->expr()->eq('c.id', ':customerId'))
                ->setParameter('customerId', hex2bin($customerId))
                ->executeQuery()
                ->fetchOne();
                
            return (bool) $customerActive;
        } catch (\Throwable $e) {
            $this->logger->error('Error checking customer active status', [
                'exception' => $e->getMessage(),
                'customerId' => $customerId,
            ]);
            return false;
        }
    }
}
