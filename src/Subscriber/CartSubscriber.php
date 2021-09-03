<?php

namespace MailCampaigns\AbandonedCart\Subscriber;

use Exception;
use Shopware\Core\Checkout\Cart\Event\CartChangedEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class CartSubscriber implements EventSubscriberInterface
{
    private EntityRepositoryInterface $abandonedCartRepository;

    public function __construct(EntityRepositoryInterface $abandonedCartRepository)
    {
        $this->abandonedCartRepository = $abandonedCartRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CartChangedEvent::class => 'afterChange',
        ];
    }

    /**
     * After a cart has been changed, we no longer consider it "abandoned".
     */
    public function afterChange(CartChangedEvent $event): void
    {
        try {
            $abandonedCartId = $this->findAbandonedCartIdByToken($event->getCart()->getToken());

            if ($abandonedCartId !== null) {
                $this->abandonedCartRepository->delete([
                    [
                        'id' => $abandonedCartId,
                    ],
                ], Context::createDefaultContext());
            }
        } catch (Exception $exception) {
            // Note: This prevents the checkout process from potentially breaking due to our extension.
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
