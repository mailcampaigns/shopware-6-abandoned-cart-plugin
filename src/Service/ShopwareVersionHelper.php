<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service;

use Shopware\Core\Kernel;

class ShopwareVersionHelper
{
    /**
     * Returns the major and minor version of the Shopware instance.
     *
     * @return string
     */
    public function getMajorMinorShopwareVersion(): string
    {
        $version = Kernel::SHOPWARE_FALLBACK_VERSION;
        $versionParts = explode('.', $version); // Split version by dots
        return $versionParts[0] . '.' . $versionParts[1]; // Return major and minor version
    }
}