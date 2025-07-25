<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Subscriber;

use MailCampaigns\AbandonedCart\Core\Checkout\Cart\ModificationTimeStruct;
use Shopware\Core\Checkout\Cart\AbstractCartPersister;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Event\CartChangedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartChangeSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AbstractCartPersister $cartPersister)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartChangedEvent::class => 'onCartChanged',
        ];
    }

    public function onCartChanged(CartChangedEvent $event): void
    {
        $this->refreshModificationTime($event->getCart(), $event->getContext());
    }

    private function refreshModificationTime(Cart $cart, SalesChannelContext $context): void
    {
        /** @var ModificationTimeStruct|null $extension */
        $modificationTimeExtension = $cart->getExtension(ModificationTimeStruct::CART_EXTENSION_NAME);

        if (!$modificationTimeExtension) {
            $modificationTimeExtension = new ModificationTimeStruct(new \DateTime());
            $cart->addExtension(ModificationTimeStruct::CART_EXTENSION_NAME, $modificationTimeExtension);
        }

        $modificationTimeExtension->setModifiedAt(new \DateTime());

        $this->cartPersister->save($cart, $context);
    }
}
