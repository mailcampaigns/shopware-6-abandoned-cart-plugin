<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart;

use MailCampaigns\AbandonedCart\Migration\Migration1725548117CreateAbandonedCartTable;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Doctrine\DBAL\Connection;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class MailCampaignsAbandonedCart extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $container = $this->container;
        $connection = $container->get(Connection::class);

        $migration = new Migration1725548117CreateAbandonedCartTable();
        $migration->updateDestructive($connection);
    }
}
