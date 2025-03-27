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
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
#[AsCommand(
    name: 'mailcampaigns:abandoned-cart:mark',
    description: "Marks shopping carts as 'abandoned' if they are older than the configured timeout."
)]
final class MarkAbandonedCartCommand extends Command
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
        $cnt = $this->manager->generate();

        $output->writeln("Marked $cnt shopping carts as \"abandoned\".");

        return Command::SUCCESS;
    }
}
