<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart\Event;

use Shopware\Core\Framework\Context;
use Symfony\Contracts\EventDispatcher\Event;

class AfterAbandonedCartDeletedEvent extends Event
{
    public function __construct(
        protected string $abandonedCartId,
        protected string $token,
        protected Context $context
    )
    {
    }

    public function getAbandonedCartId(): string
    {
        return $this->abandonedCartId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
