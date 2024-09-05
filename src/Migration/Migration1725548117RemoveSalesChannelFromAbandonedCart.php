<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Removes the `sales_channel_id` column from the `abandoned_cart` table for Shopware 6.6 compatibility.
 *
 * @author Max Seelig <max.seelig@heroesonly.com>
 */
final class Migration1725548117RemoveSalesChannelFromAbandonedCart extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1725548117;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
            ALTER TABLE `abandoned_cart`
            DROP COLUMN `sales_channel_id`;
        SQL);
    }

    /**
     * @throws Exception
     */
    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
            ALTER TABLE `abandoned_cart`
            ADD COLUMN `sales_channel_id` BINARY(16) NOT NULL AFTER `customer_id`,
            ADD INDEX `sales_channel_id` (`sales_channel_id`);
        SQL);
    }
}
