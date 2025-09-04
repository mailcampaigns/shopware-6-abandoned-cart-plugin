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
 * @author Max Seelig <max.seelig@heroesonly.com>
 */
#[AsMessageHandler(handles: UpdateAbandonedCartTask::class)]
final class UpdateAbandonedCartTaskHandler extends AbstractVersionCompatibleScheduledTaskHandler
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
        $this->manager->updateAbandonedCarts();
    }
}
