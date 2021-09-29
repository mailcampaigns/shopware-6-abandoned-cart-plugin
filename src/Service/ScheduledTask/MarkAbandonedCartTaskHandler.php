<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartFactory;
use MailCampaigns\AbandonedCart\Core\Checkout\Cart\CartRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class MarkAbandonedCartTaskHandler extends ScheduledTaskHandler
{
    /**
     * @var CartRepository
     */
    private $cartRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $abandonedCartRepository;

    public function __construct(
        CartRepository $cartRepository,
        EntityRepositoryInterface $abandonedCartRepository,
        EntityRepositoryInterface $scheduledTaskRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->abandonedCartRepository = $abandonedCartRepository;

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
        foreach ($this->cartRepository->findMarkableAsAbandoned() as $cart) {
            $abandonedCart = AbandonedCartFactory::createFromArray($cart);

            $this->abandonedCartRepository->create([
                [
                    'cartToken' => $abandonedCart->getCartToken(),
                    'price' => $abandonedCart->getPrice(),
                    'lineItems' => $abandonedCart->getLineItems(),
                    'customerId' => $abandonedCart->getCustomerId(),
                    'salesChannelId' => $abandonedCart->getSalesChannelId(),
                ],
            ], Context::createDefaultContext());
        }
    }
}
