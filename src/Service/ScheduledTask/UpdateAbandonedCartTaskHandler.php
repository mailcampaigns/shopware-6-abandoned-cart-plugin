<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @author Max Seelig <max.seelig@heroesonly.com>
 */
#[AsMessageHandler(handles: UpdateAbandonedCartTask::class)]
final class UpdateAbandonedCartTaskHandler extends ScheduledTaskHandler
{
    public function __construct(private readonly AbandonedCartManager $manager)
    {
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $this->manager->updateAbandonedCarts();
    }
}
