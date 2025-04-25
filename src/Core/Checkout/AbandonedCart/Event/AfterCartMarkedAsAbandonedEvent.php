<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\Event;

use MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\AbandonedCartEntity;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\EventDispatcher\Event;

class AfterCartMarkedAsAbandonedEvent extends Event
{
    public function __construct(
        protected AbandonedCartEntity $abandonedCart,
        protected array $cartData,
        protected Context $context
    )
    {
    }

    public function getAbandonedCart(): AbandonedCartEntity
    {
        return $this->abandonedCart;
    }

    public function getCartData(): array
    {
        return $this->cartData;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
