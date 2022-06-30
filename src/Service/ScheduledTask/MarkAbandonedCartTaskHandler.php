<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class MarkAbandonedCartTaskHandler extends ScheduledTaskHandler
{
    private AbandonedCartManager $manager;
    private EntityRepositoryInterface $abandonedCartRepository;

    public function __construct(AbandonedCartManager $manager, EntityRepositoryInterface $scheduledTaskRepository)
    {
        $this->manager = $manager;

        parent::__construct($scheduledTaskRepository);
    }

    /**
     * {@inheritdoc}
     */
    public static function getHandledMessages(): iterable
    {
        yield MarkAbandonedCartTask::class;
    }

    public function run(): void
    {
        $this->manager->generate();
    }
}
