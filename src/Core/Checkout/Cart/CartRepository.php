<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Service\ShopwareVersionHelper;
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
    ) {
    }

    /**
     * Finds and returns an array of `cart` records that are considered "abandoned" and meet specific criteria.
     * 
     * This method performs the following steps:
     * 1. Retrieves cart records that are not marked as abandoned OR have been updated after being marked as abandoned. Depending on the $retrieveUpdated parameter.
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

        $selectAbandonedCartTokensQuery = $this->generateAbandonedCartTokensQuery();

        $qb = $this->connection->createQueryBuilder();

        $field = $this->payloadExists() ? 'payload' : 'cart';
        if($this->versionHelper->getMajorMinorShopwareVersion() === '6.5') {
            $qb->select("c.token, cart.$field AS payload, c.created_at, c.updated_at AS c_updated_at, ac.updated_at AS ac_updated_at")
                ->from('cart', 'c')
                ->leftJoin('c', 'abandoned_cart', 'ac', 'c.token = ac.cart_token')
                ->where($qb->expr()->in('c.token', $selectAbandonedCartTokensQuery))
                ->orderBy('c.created_at', 'ASC')
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
            $qb->select("c.token, cart.$field AS payload, c.created_at', 'ac.updated_at")
                ->from('cart', 'c')
                ->leftJoin('c', 'abandoned_cart', 'ac', 'c.token = ac.cart_token')
                ->where($qb->expr()->in('c.token', $selectAbandonedCartTokensQuery))
                ->orderBy('c.created_at', 'ASC')
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

        // Return only carts with a customer ID.
        $data = array_filter($data, function ($cart) {
            $cart = unserialize($cart['payload']);

            $firstAddress = $cart->getDeliveries()->getAddresses()->first();
            if($firstAddress) {
                $customerId = $firstAddress->getCustomerId();
                if($customerId) {
                    return true;
                }
            }
            return false;
        });

        // Loop over results
        foreach($data as $key => $cart) {
            $cart = unserialize($cart['payload']);

            // Remove carts that are marked as recalculated since they can be considered as garbage
            if($cart->getBehavior()->isRecalculation()) {
                unset($data[$key]);
                continue;
            }

            // Add customer ID to result
            $data[$key]['customer_id'] = $cart->getDeliveries()->getAddresses()->first()->getCustomerId();

            // Add price to each result
            $data[$key]['price'] = $cart->getPrice()->getTotalPrice();

            // Add line item count to result
            $data[$key]['line_item_count'] = count($cart->getLineItems());

            // Remove customers that are inactive
            $qb = $this->connection->createQueryBuilder();
            $qb->select('c.id')
                ->from('customer', 'c')
                ->where($qb->expr()->eq('c.id', ':customerId'))
                ->andWhere($qb->expr()->eq('c.active', ':active'))
                ->setParameter('customerId', hex2bin($data[$key]['customer_id']))
                ->setParameter('active', 1);
            $result = $qb->executeQuery()->fetchOne();

            if($result === false) {
                unset($data[$key]);
                continue;
            }

            // Remove customers that have placed an order after the cart was created
            $qb = $this->connection->createQueryBuilder();
            $qb->select('oc.id')
                ->from('order_customer', 'oc')
                ->leftJoin('oc', 'customer', 'c', 'oc.customer_id = c.id')
                ->where($qb->expr()->eq('oc.customer_id', ':customerId'))
                ->andWhere($qb->expr()->gte('oc.created_at', ':cartCreatedAt'))
                ->setParameter('customerId', $data[$key]['customer_id'])
                ->setParameter('cartCreatedAt', $data[$key]['created_at']);
        }

        return $data;
    }

    /**
     * Returns an array of cart tokens that are considered "abandoned" and no longer exist in the cart table,
     * but still have an association in the abandoned_cart table.
     * @throws Exception
     */
    public function findOrphanedAbandonedCartTokens(): array
    {
        $selectAbandonedCartTokensQuery = $this->generateAbandonedCartTokensQuery();

        $statement = $this->connection->prepare(<<<SQL
            SELECT
                abandoned_cart.cart_token AS token
            FROM abandoned_cart

            LEFT JOIN cart ON abandoned_cart.cart_token = cart.token
                AND cart.`token` IN ($selectAbandonedCartTokensQuery)

            WHERE cart.token IS NULL;
        SQL);

        return array_column(
            $statement->executeQuery()->fetchAllAssociative(),
            'token'
        );
    }

    /**
     * Generates an SQL query to retrieve tokens of carts that are considered abandoned.
     *
     * This function constructs an SQL query that selects the most recent cart token
     * for each customer whose cart has been abandoned. A cart is considered abandoned
     * if it was created before a certain time threshold, which is determined by the
     * 'MailCampaignsAbandonedCart.config.markAbandonedAfter' configuration setting.
     *
     * @return string The SQL query string to retrieve abandoned cart tokens.
     */
    private function generateAbandonedCartTokensQuery(): string
    {
        $considerAbandonedAfter = (new DateTime())->modify(sprintf(
            '-%d seconds',
            $this->systemConfigService->get('MailCampaignsAbandonedCart.config.markAbandonedAfter')
        ));

        if($this->versionHelper->getMajorMinorShopwareVersion() === '6.5') {
            return <<<SQL
                SELECT
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(cart.`token` ORDER BY IFNULL(cart.updated_at, cart.created_at) DESC),
                        ',',
                        1
                    ) AS `token`
                FROM cart
                WHERE IFNULL(cart.updated_at, cart.created_at) < '{$considerAbandonedAfter->format('Y-m-d H:i:s.v')}'
                AND cart.customer_id IS NOT NULL
                
                UNION

                SELECT 'dummy-cart'
            SQL;
        }
        else if ($this->versionHelper->getMajorMinorShopwareVersion() === '6.6') {
            return <<<SQL
                SELECT
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(cart.`token` ORDER BY cart.created_at DESC),
                        ',',
                        1
                    ) AS `token`
                FROM cart
                WHERE cart.created_at < '{$considerAbandonedAfter->format('Y-m-d H:i:s.v')}'

                UNION

                SELECT 'dummy-cart'
            SQL;
        }
        else {
            throw new \RuntimeException('Unsupported Shopware version ' . $this->versionHelper->getMajorMinorShopwareVersion());
        }
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
}
