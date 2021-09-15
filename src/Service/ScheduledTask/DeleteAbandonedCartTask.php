<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class DeleteAbandonedCartTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'mailcampaigns.delete_abandoned_cart';
    }

    /**
     * {@inheritdoc}
     */
    public static function getDefaultInterval(): int
    {
        return 60;
    }
}
