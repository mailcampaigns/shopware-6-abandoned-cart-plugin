<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class CartRepository
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
     */
    public function findMarkableAsAbandoned(): array
    {
        $selectAbandonedCartNamesQuery = $this->getAbandonedCartNamesQuery();

        $field = $this->payloadExists() ? 'payload' : 'cart';
        $statement = $this->connection->prepare(<<<SQL
            SELECT
                cart.token,
                cart.name,
                cart.$field AS payload,
                cart.price,
                cart.line_item_count,
                LOWER(HEX(cart.currency_id)) AS currency_id,
                LOWER(HEX(cart.shipping_method_id)) AS shipping_method_id,
                LOWER(HEX(cart.payment_method_id)) AS payment_method_id,
                LOWER(HEX(cart.country_id)) AS country_id,
                LOWER(HEX(cart.customer_id)) AS customer_id,
                LOWER(HEX(cart.sales_channel_id)) AS sales_channel_id,
                cart.created_at
            FROM cart

            LEFT JOIN abandoned_cart ON cart.token = abandoned_cart.cart_token
                AND cart.sales_channel_id = abandoned_cart.sales_channel_id

            WHERE abandoned_cart.id IS NULL
            AND cart.`name` IN ($selectAbandonedCartNamesQuery)

            ORDER BY cart.created_at
            LIMIT 100;
        SQL);

        return $statement
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Returns an array of `cart` tokens which no longer exists or considered as "abandoned"
     * but still has an `abandoned_cart` association.
     */
    public function findTokensForUpdatedOrDeletedWithAbandonedCartAssociation(): array
    {
        $selectAbandonedCartNamesQuery = $this->getAbandonedCartNamesQuery();

        $statement = $this->connection->prepare(<<<SQL
            SELECT
                abandoned_cart.cart_token AS token
            FROM abandoned_cart

            LEFT JOIN cart ON abandoned_cart.cart_token = cart.token
                AND cart.`name` IN ($selectAbandonedCartNamesQuery)

            WHERE cart.token IS NULL;
        SQL);

        return array_column(
            $statement->executeQuery()->fetchAllAssociative(),
            'token'
        );
    }

    /**
     * Can be used for backwards compatibility fixes for < Shopware 6.4.12.
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

    private function getAbandonedCartNamesQuery(): string
    {
        $considerAbandonedAfter = (new \DateTime())->modify(sprintf(
            '-%d seconds',
            $this->systemConfigService->get('MailCampaignsAbandonedCart.config.markAbandonedAfter')
        ));

        return <<<SQL
            SELECT
                /* A customer can have multiple cart records. Select the most recent. */
                SUBSTRING_INDEX(
                    GROUP_CONCAT(cart.`name` ORDER BY IFNULL(cart.updated_at, cart.created_at) DESC),
                    ',',
                    1
                ) AS `name`
            FROM cart

            /* Exclude for inactive customers. */
            JOIN customer ON cart.customer_id = customer.id
                AND cart.sales_channel_id = customer.sales_channel_id
                AND customer.active = 1

            /* Exclude for customers with an order placed after their last cart record. */
            LEFT JOIN order_customer ON customer.id = order_customer.customer_id
                AND order_customer.created_at >= IFNULL(cart.updated_at, cart.created_at)

            WHERE IFNULL(cart.updated_at, cart.created_at) < '{$considerAbandonedAfter->format('Y-m-d H:i:s.v')}'
            AND cart.`name` != 'recalculation'
            AND order_customer.order_id IS NULL

            GROUP BY cart.customer_id

            /* Prevent empty subselect. */
            UNION

            SELECT 'dummy-cart'
        SQL;
    }
}
