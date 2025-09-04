<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use MailCampaigns\AbandonedCart\Service\ShopwareVersionHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * Abstract base class that provides version compatibility for ScheduledTaskHandler
 * between Shopware 6.6 and 6.7
 */
abstract class AbstractVersionCompatibleScheduledTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        ?LoggerInterface $exceptionLogger = null,
        ?ShopwareVersionHelper $versionHelper = null
    ) {
        // For backward compatibility, create version helper if not provided
        if ($versionHelper === null) {
            $versionHelper = new ShopwareVersionHelper();
        }
        
        $version = $versionHelper->getMajorMinorShopwareVersion();
        
        if (version_compare($version, '6.7', '>=')) {
            // Shopware 6.7+ - requires logger
            if ($exceptionLogger === null) {
                throw new \InvalidArgumentException('LoggerInterface is required for Shopware 6.7+');
            }
            parent::__construct($scheduledTaskRepository, $exceptionLogger);
        } else {
            // Shopware 6.6 - only requires repository
            parent::__construct($scheduledTaskRepository);
        }
    }
}
