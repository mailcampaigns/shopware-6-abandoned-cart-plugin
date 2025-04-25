<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1745577950RemoveLineItemsColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1745577950;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `abandoned_cart` DROP COLUMN `line_items`;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
