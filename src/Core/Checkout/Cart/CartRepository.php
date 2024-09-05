<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
final class CartRepository
{
    private Connection $connection;
    private SystemConfigService $systemConfigService;

    public function __construct(
        Connection $connection,
        SystemConfigService $systemConfigService
    ) {
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * Returns an array of `cart` records which can be considered as "abandoned".
     * @throws Exception
     */
    public function findMarkableAsAbandoned(): array
    {
        $selectAbandonedCartTokensQuery = $this->getAbandonedCartTokensQuery();

        $qb = $this->connection->createQueryBuilder();

        $qb->select('c.token, c.payload, c.created_at')
            ->from('cart', 'c')
            ->leftJoin('c', 'abandoned_cart', 'ac', 'c.token = ac.cart_token')
            ->where($qb->expr()->isNull('ac.id'))
            ->andWhere($qb->expr()->in('c.token', $selectAbandonedCartTokensQuery))
            ->orderBy('c.created_at', 'ASC')
            ->setMaxResults(100);

        $data = $qb->executeQuery()->fetchAllAssociative();

        // Return only carts with a customer ID.
        $data = array_filter($data, function ($cart) {
            $cart = unserialize($cart['payload']);

            $firstAddress = $cart->getDeliveries()->getAddresses()->first();
            if($firstAddress) {
                $customerId = $firstAddress->getCustomerId();
                if($customerId) {
                    // Add the customer ID to the result
                    $cart->customer_id = $customerId;
                    return true;
                }
            }
            return false;
        });

        // Add price to result
        $data = array_map(function ($cart) {
            $cart = unserialize($cart['payload']);
            $cart->price = (float)$cart->getPrice()->getTotalPrice();
            return $cart;
        }, $data);

        // Add line item count to result
        $data = array_map(function ($cart) {
            $cart->line_item_count = count($cart->getLineItems());
            return $cart;
        }, $data);

        return $data;
    }

    /**
     * Returns an array of `cart` tokens which no longer exists or considered as "abandoned"
     * but still has an `abandoned_cart` association.
     * @throws Exception
     */
    public function findTokensForUpdatedOrDeletedWithAbandonedCartAssociation(): array
    {
        $selectAbandonedCartTokensQuery = $this->getAbandonedCartTokensQuery();

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

    private function getAbandonedCartTokensQuery(): string
    {
        $considerAbandonedAfter = (new DateTime())->modify(sprintf(
            '-%d seconds',
            $this->systemConfigService->get('MailCampaignsAbandonedCart.config.markAbandonedAfter')
        ));

        return <<<SQL
            SELECT
                /* A customer can have multiple cart records. Select the most recent. */
                SUBSTRING_INDEX(
                    GROUP_CONCAT(cart.`token` ORDER BY IFNULL(cart.updated_at, cart.created_at) DESC),
                    ',',
                    1
                ) AS `token`
            FROM cart

            /* Exclude for inactive customers. */
            JOIN customer ON cart.customer_id = customer.id
                AND cart.sales_channel_id = customer.sales_channel_id
                AND customer.active = 1

            /* Exclude for customers with an order placed after their last cart record. */
            LEFT JOIN order_customer ON customer.id = order_customer.customer_id
                AND order_customer.created_at >= IFNULL(cart.updated_at, cart.created_at)

            WHERE IFNULL(cart.updated_at, cart.created_at) < '{$considerAbandonedAfter->format('Y-m-d H:i:s.v')}'
            AND order_customer.order_id IS NULL

            GROUP BY cart.customer_id

            /* Prevent empty subselect. */
            UNION

            SELECT 'dummy-cart'
        SQL;
    }
}
