<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\Event\AfterAbandonedCartDeletedEvent;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\Event\AfterAbandonedCartUpdatedEvent;
use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\Event\AfterCartMarkedAsAbandonedEvent;
use MailCampaigns\AbandonedCart\Core\Checkout\Cart\CartRepository;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
final class AbandonedCartManager
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly EntityRepository $abandonedCartRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return int The number of generated "abandoned" carts.
     * @throws Exception
     */
    public function generate(): int
    {
        $cnt = 0;

        $context = new Context(new SystemSource());

        foreach ($this->cartRepository->findAbandonedCartsWithCriteria(false) as $cart) {
            $abandonedCart = AbandonedCartFactory::createFromArray($cart);

            $this->abandonedCartRepository->upsert([
                [
                    'cartToken' => $abandonedCart->getCartToken(),
                    'price' => $abandonedCart->getPrice(),
                    'lineItems' => $abandonedCart->getLineItems(),
                    'customerId' => $abandonedCart->getCustomerId(),
                ],
            ], $context);

            $this->eventDispatcher->dispatch(new AfterCartMarkedAsAbandonedEvent($abandonedCart, $cart, $context));

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

        $context = new Context(new SystemSource());

        foreach ($this->cartRepository->findAbandonedCartsWithCriteria(true) as $cart) {
            $abandonedCart = AbandonedCartFactory::createFromArray($cart);

            // Get the abandoned cart ID by token.
            $abandonedCartId = $this->findAbandonedCartIdByToken($abandonedCart->getCartToken());

            $abandonedCart->setId($abandonedCartId);

            $this->abandonedCartRepository->upsert([
                [
                    'id' => $abandonedCartId,
                    'price' => $abandonedCart->getPrice(),
                    'lineItems' => $abandonedCart->getLineItems(),
                ],
            ], $context);

            $this->eventDispatcher->dispatch(new AfterAbandonedCartUpdatedEvent($abandonedCart, $cart, $context));

            $cnt++;
        }

        return $cnt;
    }

    /**
     * @return int The number of deleted "abandoned" carts.
     * @throws Exception
     */
    public function cleanUp(): int
    {
        $cnt = 0;

        $context = new Context(new SystemSource());

        foreach ($this->cartRepository->findOrphanedAbandonedCartTokens() as $token) {
            $abandonedCartId = $this->findAbandonedCartIdByToken($token);

            if ($abandonedCartId !== null) {
                $this->abandonedCartRepository->delete([
                    [
                        'id' => $abandonedCartId,
                    ],
                ], $context);

                $this->eventDispatcher->dispatch(new AfterAbandonedCartDeletedEvent($abandonedCartId, $token, $context));

                $cnt++;
            }
        }

        return $cnt;
    }

    private function findAbandonedCartIdByToken(string $token): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('cartToken', $token));

        return $this->abandonedCartRepository
            ->searchIds($criteria, new Context(new SystemSource()))
            ->firstId();
    }
}
