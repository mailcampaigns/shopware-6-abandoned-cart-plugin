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
 * @author Max Seelig <max.seelig@heroesonly.com>
 */
#[AsCommand(
    name: 'mailcampaigns:abandoned-cart:update',
    description: 'Updates "abandoned" carts that have been modified since they were marked as abandoned.'
)]
final class UpdateAbandonedCartCommand extends Command
{
    private AbandonedCartManager $manager;

    public function __construct(AbandonedCartManager $manager, string $name = null)
    {
        $this->manager = $manager;
        parent::__construct($name);
    }

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $cnt = $this->manager->updateAbandonedCarts();
        $output->writeln("Updated $cnt \"abandoned\" shopping carts.");
        return Command::SUCCESS;
    }
}