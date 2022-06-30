<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Command;

use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class MarkAbandonedCartCommand extends Command
{
    protected static $defaultName = 'mailcampaigns:abandoned-cart:mark';
    protected static $defaultDescription = 'Marks shopping carts older than the configured time as "abandoned".';

    private AbandonedCartManager $manager;

    public function __construct(AbandonedCartManager $manager, string $name = null)
    {
        $this->manager = $manager;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cnt = $this->manager->generate();

        $output->writeln("Marked $cnt shopping carts as \"abandoned\".");

        return Command::SUCCESS;
    }
}
