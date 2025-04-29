<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @author Ruslan Belziuk <ruslan@dumka.pro>
 */
class RelaunchAbandonedCartSchedulerTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'mailcampaigns.abandoned_cart.relaunch';
    }

    public static function getDefaultInterval(): int
    {
        return 300;
    }
}
