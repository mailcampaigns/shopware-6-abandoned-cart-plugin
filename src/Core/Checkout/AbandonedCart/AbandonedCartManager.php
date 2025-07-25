<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\Event\AfterAbandonedCartUpdatedEvent;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\Event\AfterCartMarkedAsAbandonedEvent;
use MailCampaigns\AbandonedCart\Core\Checkout\Cart\CartRepository;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
final class AbandonedCartManager
{
    private Context $context;

    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly EntityRepository $abandonedCartRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityRepository $scheduledTaskRepository
    ) {
        $this->context = new Context(new SystemSource());
    }

    /**
     * @return int The number of generated "abandoned" carts.
     * @throws Exception
     */
    public function generate(): int
    {
        $cnt = 0;

        foreach ($this->cartRepository->findAbandonedCartsWithCriteria(false) as $cart) {
            $abandonedCart = AbandonedCartFactory::createFromArray($cart);

            $this->abandonedCartRepository->upsert([
                [
                    'cartToken' => $abandonedCart->getCartToken(),
                    'price' => $abandonedCart->getPrice(),
                    'customerId' => $abandonedCart->getCustomerId(),
                ],
            ], $this->context);

            $this->eventDispatcher->dispatch(new AfterCartMarkedAsAbandonedEvent($abandonedCart, $cart, $this->context));

            $cnt++;
        }

        return $cnt;
    }

    /**
     * Updates abandoned carts that have been modified since they were marked as abandoned.
     *
     * @throws Exception
     */
    public function updateAbandonedCarts(): int
    {
        $cnt = 0;

        $considerAbandonedAfter = (new \DateTime())->modify(sprintf(
            '-%d seconds',
            $this->systemConfigService->get('MailCampaignsAbandonedCart.config.markAbandonedAfter')
        ))->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($this->cartRepository->findAbandonedCartsWithCriteria(true) as $cart) {
            $abandonedCart = AbandonedCartFactory::createFromArray($cart);

            // Get the abandoned cart ID by token.
            $abandonedCartId = $this->findAbandonedCartIdByToken($abandonedCart->getCartToken());
            if ($abandonedCartId === null) {
                continue;
            }

            $abandonedCart->setId($abandonedCartId);

            $this->abandonedCartRepository->upsert([
                [
                    'id' => $abandonedCartId,
                    'price' => $abandonedCart->getPrice(),
                ],
            ], $this->context);

            if ($cart['modified_at'] < $considerAbandonedAfter) {
                $this->eventDispatcher->dispatch(new AfterAbandonedCartUpdatedEvent($abandonedCart, $cart, $this->context));
            }

            $cnt++;
        }

        return $cnt;
    }

    private function findAbandonedCartIdByToken(string $token): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('cartToken', $token));

        return $this->abandonedCartRepository
            ->searchIds($criteria, $this->context)
            ->firstId();
    }

    public function relaunchTasks(): void
    {
        $criteria = $this->buildCriteriaForStuckScheduledTasks();
        $context = Context::createDefaultContext();
        $tasks = $this->scheduledTaskRepository->search($criteria, $context)->getEntities();

        if (\count($tasks) === 0) {
            return;
        }

        /** @var ScheduledTaskEntity $task */
        foreach ($tasks as $task) {
            $this->scheduledTaskRepository->update([
                [
                    'id' => $task->getId(),
                    'status' => ScheduledTaskDefinition::STATUS_SCHEDULED,
                ],
            ], $context);
        }
    }

    private function buildCriteriaForStuckScheduledTasks(): Criteria
    {
        $considerTasksAsStuck = (new \DateTime())->modify('-1 hour');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', ScheduledTaskDefinition::STATUS_RUNNING));
        $criteria->addFilter(new EqualsAnyFilter('name', [
            'mailcampaigns.abandoned_cart.mark',
            'mailcampaigns.abandoned_cart.update',
        ]));
        $criteria->addFilter(new RangeFilter(
            'updatedAt',
            [
                RangeFilter::LT => $considerTasksAsStuck->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        ));
        $criteria->addFilter(new RangeFilter(
            'lastExecutionTime',
            [
                RangeFilter::LT => $considerTasksAsStuck->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        ));

        return $criteria;
    }
}
