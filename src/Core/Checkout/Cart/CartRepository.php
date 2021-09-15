<?php

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
        $markAbandonedAfter = (new \DateTime())->modify(sprintf(
            '-%d seconds',
            $this->systemConfigService->get('MailCampaignsAbandonedCart.config.markAbandonedAfter')
        ));

        $sql = <<<SQL
SELECT
    `cart`.`token`,
    `cart`.`name`,
    `cart`.`cart`,
    `cart`.`price`,
    `cart`.`line_item_count`,
    LOWER(HEX(`cart`.`currency_id`)) AS `currency_id`,
    LOWER(HEX(`cart`.`shipping_method_id`)) AS `shipping_method_id`,
    LOWER(HEX(`cart`.`payment_method_id`)) AS `payment_method_id`,
    LOWER(HEX(`cart`.`country_id`)) AS `country_id`,
    LOWER(HEX(`cart`.`customer_id`)) AS `customer_id`,
    LOWER(HEX(`cart`.`sales_channel_id`)) AS `sales_channel_id`,
    `cart`.`created_at`
FROM `cart`

JOIN `customer` ON `cart`.`customer_id` = `customer`.`id`
    AND `cart`.`sales_channel_id` = `customer`.`sales_channel_id`
    AND `customer`.`active` = 1

LEFT JOIN `abandoned_cart` ON `cart`.`token` = `abandoned_cart`.`cart_token`
    AND `cart`.`sales_channel_id` = `abandoned_cart`.`sales_channel_id`

WHERE `abandoned_cart`.`id` IS NULL
AND `cart`.`customer_id` IS NOT NULL
AND `cart`.`created_at` < '{$markAbandonedAfter->format('Y-m-d H:i:s.v')}'

ORDER BY `cart`.`created_at`
LIMIT 100;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        return $statement->fetchAllAssociative();
    }

    /**
     * Returns an array of `cart` tokens which no longer exists but still has an `abandoned_cart` association.
     */
    public function findDeletedTokensWithAbandonedCartAssociation(): array
    {
        $sql = <<<SQL
SELECT
    `abandoned_cart`.`cart_token` AS `token`
FROM `abandoned_cart`

LEFT JOIN `cart` ON `abandoned_cart`.`cart_token` = `cart`.`token`

WHERE `cart`.`token` IS NULL;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        return array_column(
            $statement->fetchAllAssociative(),
            'token'
        );
    }
}
