<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart;

use Doctrine\DBAL\Connection;
use MailCampaigns\AbandonedCart\Migration\Migration1725548117CreateAbandonedCartTable;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class MailCampaignsAbandonedCart extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $container = $this->container;
        $connection = $container->get(Connection::class);

        $migration = new Migration1725548117CreateAbandonedCartTable();
        $migration->updateDestructive($connection);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);

        // Unschedule all tasks related to this plugin
        $container = $this->container;
        /** @var EntityRepository $scheduledTaskRepository */
        $scheduledTaskRepository = $container->get('scheduled_task.repository');

        // Dynamically fetch all tasks registered by this plugin
        $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
        $criteria->addFilter(new ContainsFilter('name', 'mailcampaigns.abandoned_cart'));

        $existingTasks = $scheduledTaskRepository->search($criteria, $deactivateContext->getContext());

        if ($existingTasks->getTotal() > 0) {
            $updates = [];
            foreach ($existingTasks as $task) {
                $updates[] = [
                    'id' => $task->getUniqueIdentifier(),
                    'status' => ScheduledTaskDefinition::STATUS_INACTIVE,
                ];
            }

            $scheduledTaskRepository->update($updates, $deactivateContext->getContext());
        }
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        // Reschedule all tasks related to this plugin
        $container = $this->container;
        /** @var EntityRepository $scheduledTaskRepository */
        $scheduledTaskRepository = $container->get('scheduled_task.repository');

        // Dynamically fetch all tasks registered by this plugin
        $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
        $criteria->addFilter(new ContainsFilter('name', 'mailcampaigns.abandoned_cart'));

        $existingTasks = $scheduledTaskRepository->search($criteria, $activateContext->getContext());

        if ($existingTasks->getTotal() > 0) {
            $updates = [];
            foreach ($existingTasks as $task) {
                $updates[] = [
                    'id' => $task->getUniqueIdentifier(),
                    'status' => ScheduledTaskDefinition::STATUS_SCHEDULED,
                ];
            }

            $scheduledTaskRepository->update($updates, $activateContext->getContext());
        }
    }
}
