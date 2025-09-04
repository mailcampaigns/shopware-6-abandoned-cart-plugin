<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart\Handler;

use Doctrine\DBAL\Connection;
use MailCampaigns\AbandonedCart\Service\ShopwareVersionHelper;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating version-specific cart repository handlers
 */
class CartRepositoryHandlerFactory
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ShopwareVersionHelper $versionHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createHandler(): CartRepositoryHandlerInterface
    {
        $version = $this->versionHelper->getMajorMinorShopwareVersion();

        return match ($version) {
            '6.5' => new Shopware65CartRepositoryHandler($this->connection, $this->logger),
            '6.6', '6.7' => new Shopware66CartRepositoryHandler($this->connection, $this->logger), // 6.7 uses same handler as 6.6
            default => throw new \RuntimeException('Unsupported Shopware version ' . $version),
        };
    }
}
