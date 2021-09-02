<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class MarkAbandonedCartTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'mailcampaigns.mark_abandoned_cart';
    }

    /**
     * {@inheritdoc}
     */
    public static function getDefaultInterval(): int
    {
        return 300;
    }
}
