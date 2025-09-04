<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use MailCampaigns\AbandonedCart\Service\ShopwareVersionHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @author Ruslan Belziuk <ruslan@dumka.pro>
 */
#[AsMessageHandler(handles: RelaunchAbandonedCartSchedulerTask::class)]
final class RelaunchAbandonedCartSchedulerTaskHandler extends AbstractVersionCompatibleScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly AbandonedCartManager $manager,
        ?LoggerInterface $exceptionLogger = null,
        ?ShopwareVersionHelper $versionHelper = null
    ) {
        parent::__construct($scheduledTaskRepository, $exceptionLogger, $versionHelper);
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $this->manager->relaunchTasks();
    }
}
