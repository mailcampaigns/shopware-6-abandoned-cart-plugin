<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @author Ruslan Belziuk <ruslan@dumka.pro>
 */
#[AsMessageHandler(handles: RelaunchAbandonedCartSchedulerTask::class)]
final class RelaunchAbandonedCartSchedulerTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly AbandonedCartManager $manager
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $this->manager->relaunchTasks();
    }
}
