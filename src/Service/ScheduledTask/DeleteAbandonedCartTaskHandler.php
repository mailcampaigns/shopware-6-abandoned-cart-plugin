<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Service\ScheduledTask;

use MailCampaigns\AbandonedCart\Core\Checkout\Cart\CartRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class DeleteAbandonedCartTaskHandler extends ScheduledTaskHandler
{
    private CartRepository $cartRepository;

    private EntityRepositoryInterface $abandonedCartRepository;

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
        foreach ($this->cartRepository->findDeletedTokensWithAbandonedCartAssociation() as $token) {
            $abandonedCartId = $this->findAbandonedCartIdByToken($token);

            if ($abandonedCartId !== null) {
                $this->abandonedCartRepository->delete([
                    [
                        'id' => $abandonedCartId,
                    ],
                ], Context::createDefaultContext());
            }
        }
    }

    private function findAbandonedCartIdByToken(string $token): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('cartToken', $token));

        return $this->abandonedCartRepository
            ->searchIds($criteria, Context::createDefaultContext())
            ->firstId();
    }
}
