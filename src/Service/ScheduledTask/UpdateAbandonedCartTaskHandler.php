<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @author Max Seelig <max.seelig@heroesonly.com>
 */
#[AsMessageHandler]
final class UpdateAbandonedCartTaskHandler
{
    public function __construct(private readonly AbandonedCartManager $manager)
    {
    }

    /**
     * @throws Exception
     */
    public function __invoke(DeleteAbandonedCartTask $message): void
    {
        $this->manager->updateAbandonedCarts();
    }
}
