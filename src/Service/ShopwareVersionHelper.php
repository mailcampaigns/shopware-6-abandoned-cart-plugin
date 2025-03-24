<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service;

use Shopware\Core\Kernel;

class ShopwareVersionHelper
{
    public function getMajorMinorShopwareVersion(): string
    {
        $version = Kernel::SHOPWARE_FALLBACK_VERSION;
        return substr($version, 0, strrpos($version, '.')); // Remove patch version
    }
}