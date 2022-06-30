<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Command;

use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class DeleteAbandonedCartCommand extends Command
{
    protected static $defaultName = 'mailcampaigns:abandoned-cart:delete';
    protected static $defaultDescription = 'Deletes "abandoned" carts without an existing reference';

    private AbandonedCartManager $manager;

    public function __construct(AbandonedCartManager $manager, string $name = null)
    {
        $this->manager = $manager;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cnt = $this->manager->cleanUp();

        $output->writeln("Deleted $cnt \"abandoned\" shopping carts.");

        return Command::SUCCESS;
    }
}
