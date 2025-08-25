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
 * Cart repository handler for Shopware 6.5
 */
class Shopware65CartRepositoryHandler implements CartRepositoryHandlerInterface
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
        
        $qb->select("c.token, c.$field AS payload, c.created_at, c.updated_at AS c_updated_at, ac.updated_at AS ac_updated_at")
            ->addSelect('LOWER(HEX(c.customer_id)) AS customer_id')
            ->addSelect($this->compressedExists() ? 'c.compressed' : '0 AS compressed')
            ->addSelect('c.price')
            ->addSelect('c.line_item_count')
            ->from('cart', 'c')
            ->leftJoin('c', 'abandoned_cart', 'ac', 'c.token = ac.cart_token')
            ->where($qb->expr()->in('c.token', ':tokens'))
            ->setParameter('tokens', $abandonedCartTokens, ArrayParameterType::STRING)
            ->setMaxResults(100);

        if (!$retrieveUpdated) { // Not yet marked as abandoned
            $qb->andWhere($qb->expr()->isNull('ac.id'));
        } else { // Updated after marked as abandoned
            $qb->andWhere($qb->expr()->gt('c.updated_at', 'c.created_at'));
            $qb->andWhere(
                $qb->expr()->or(
                    $qb->expr()->isNull('ac.updated_at'),
                    $qb->expr()->gt('c.updated_at', 'ac.updated_at')
                )
            );
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Check whether the `compressed` column exists on the `cart` table.
     */
    private function compressedExists(): bool
    {
        $statement = $this->connection->prepare(<<<SQL
            SHOW COLUMNS FROM cart;
        SQL);

        return in_array('compressed', array_column(
            $statement->executeQuery()->fetchAllAssociative(),
            'Field'
        ));
    }

    public function getAbandonedCartTokensQuery(DateTime $considerAbandonedAfter): string
    {
        return <<<SQL
            SELECT
                SUBSTRING_INDEX(
                    GROUP_CONCAT(cart.`token` ORDER BY IFNULL(cart.updated_at, cart.created_at) DESC),
                    ',',
                    1
                ) AS `token`
            FROM cart
            INNER JOIN customer ON customer.id = cart.customer_id AND customer.active = 1
            WHERE IFNULL(cart.updated_at, cart.created_at) < '{$considerAbandonedAfter->format('Y-m-d H:i:s.v')}'
            AND cart.customer_id IS NOT NULL
            GROUP BY cart.customer_id
        SQL;
    }

    public function processCartRow(array $row, \Shopware\Core\Checkout\Cart\Cart $cartObj, bool $retrieveUpdated): ?array
    {
        // For Shopware 6.5, customer_id, price, and line_item_count are already in the database
        // No additional processing needed for these fields
        
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
        return '6.5';
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
}
