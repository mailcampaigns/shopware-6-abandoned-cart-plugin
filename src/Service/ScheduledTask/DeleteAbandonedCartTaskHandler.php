<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
#[AsMessageHandler(handles: DeleteAbandonedCartTask::class)]
final class DeleteAbandonedCartTaskHandler
{
    public function __construct(private readonly AbandonedCartManager $manager)
    {
    }

    public static function getHandledMessages(): iterable
    {
        yield DeleteAbandonedCartTask::class;
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $this->manager->cleanUp();
    }
}
