<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
#[AsMessageHandler]
final class MarkAbandonedCartTaskHandler
{
    public function __construct(private readonly AbandonedCartManager $manager)
    {
    }

    /**
     * @throws Exception
     */
    public function __invoke(MarkAbandonedCartTask $message): void
    {
        $this->manager->generate();
    }
}
