<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @author Max Seelig <max.seelig@heroesonly.com>
 */
class UpdateAbandonedCartTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'mailcampaigns.abandoned_cart.update';
    }

    public static function getDefaultInterval(): int
    {
        return 300; // 5 minutes
    }
}
