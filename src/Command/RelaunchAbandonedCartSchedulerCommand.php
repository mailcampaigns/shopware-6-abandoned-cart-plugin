<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Command;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ruslan Belziuk <ruslan@dumka.pro>
 */
#[AsCommand(
    name: 'mailcampaigns:abandoned-cart:relaunch',
    description: 'Relaunch abandoned cart schedules tasks.'
)]
final class RelaunchAbandonedCartSchedulerCommand extends Command
{
    public function __construct(private AbandonedCartManager $manager, string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->manager->relaunchTasks();

        $output->writeln("Rescheduled.");

        return Command::SUCCESS;
    }
}
