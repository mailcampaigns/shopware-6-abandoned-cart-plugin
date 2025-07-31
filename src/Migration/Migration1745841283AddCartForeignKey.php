<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Creates a foreign key dependency between abandoned cart and cart tables.
 *
 * @author Ruslan Belziuk <ruslan@belziuk.com>
 */
class Migration1745841283AddCartForeignKey extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1745841283;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
            DELETE `abandoned_cart`
                FROM `abandoned_cart`
            LEFT JOIN `cart` ON `cart`.`token` = `abandoned_cart`.cart_token
            WHERE cart.token IS NULL;
        SQL);

        $connection->executeStatement(<<<SQL
            ALTER TABLE `abandoned_cart`
                ADD CONSTRAINT `fk.abandoned_cart.cart` FOREIGN KEY (`cart_token`)
                    REFERENCES `cart` (`token`) ON DELETE CASCADE ON UPDATE CASCADE;
        SQL);
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
            ALTER TABLE `abandoned_cart`
                DROP FOREIGN KEY `fk.abandoned_cart.cart`;
        SQL);

        $connection->executeStatement(<<<SQL
            ALTER TABLE `abandoned_cart`
                DROP INDEX `fk.abandoned_cart.cart`;
        SQL);
    }
}
