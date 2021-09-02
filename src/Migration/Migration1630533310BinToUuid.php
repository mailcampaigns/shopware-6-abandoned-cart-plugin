<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Creates a `BIN_TO_UUID` function for pre-MySQL 8.0 versions.
 *
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class Migration1630533310BinToUuid extends MigrationStep
{
    /**
     * {@inheritdoc}
     */
    public function getCreationTimestamp(): int
    {
        return 1630533310;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Connection $connection): void
    {
        $connection->executeQuery('SELECT VERSION();');
        $sql = <<<SQL
DROP FUNCTION IF EXISTS BIN_TO_UUID;

DELIMITER //

CREATE FUNCTION BIN_TO_UUID(bin BINARY(16))
    RETURNS CHAR(36) DETERMINISTIC
BEGIN
    DECLARE hex CHAR(32);
    SET hex = HEX(bin);
    RETURN LOWER(
        CONCAT(
            LEFT(hex, 8), '-',
            MID(hex, 9, 4), '-',
            MID(hex, 13, 4), '-',
            MID(hex, 17, 4), '-',
            RIGHT(hex, 12)
        )
    );
END; //

DELIMITER ;
SQL;
        $connection->executeStatement($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}
