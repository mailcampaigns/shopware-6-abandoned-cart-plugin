<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart\Handler;

use Doctrine\DBAL\Exception;

/**
 * Interface for version-specific cart repository handlers
 */
interface CartRepositoryHandlerInterface
{
    /**
     * Build query to fetch abandoned carts with criteria
     * 
     * @param array $abandonedCartTokens
     * @param bool $retrieveUpdated
     * @return array
     * @throws Exception
     */
    public function buildAbandonedCartsQuery(array $abandonedCartTokens, bool $retrieveUpdated): array;

    /**
     * Get abandoned cart tokens query
     * 
     * @param \DateTime $considerAbandonedAfter
     * @return string
     */
    public function getAbandonedCartTokensQuery(\DateTime $considerAbandonedAfter): string;

    /**
     * Process cart data row
     * 
     * @param array $row
     * @param \Shopware\Core\Checkout\Cart\Cart $cartObj
     * @param bool $retrieveUpdated
     * @return array|null Returns null if cart should be filtered out
     */
    public function processCartRow(array $row, \Shopware\Core\Checkout\Cart\Cart $cartObj, bool $retrieveUpdated): ?array;

    /**
     * Get the supported Shopware version
     * 
     * @return string
     */
    public function getSupportedVersion(): string;
}
