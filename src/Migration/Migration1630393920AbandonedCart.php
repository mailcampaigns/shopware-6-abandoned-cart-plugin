<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Migration;

use Doctrine\DBAL\Connection;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartEntity;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Creates a table for {@see AbandonedCartEntity}.
 *
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class Migration1630393920AbandonedCart extends MigrationStep
{
    /**
     * {@inheritdoc}
     */
    public function getCreationTimestamp(): int
    {
        return 1630393920;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `abandoned_cart` (
    `id` BINARY(16) NOT NULL,
    `cart_token` VARCHAR(50) COLLATE utf8mb4_unicode_ci,
    `price` FLOAT NOT NULL,
    `line_items` JSON,
    `customer_id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY (`cart_token`),
    INDEX (`customer_id`),
    INDEX (`sales_channel_id`),
    FOREIGN KEY (`customer_id`)
        REFERENCES customer(`id`),
    FOREIGN KEY (`sales_channel_id`)
        REFERENCES sales_channel(`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;

        if (method_exists($connection, 'executeStatement') === true) {
            $connection->executeStatement($sql);
        } else {
            $connection->exec($sql);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
