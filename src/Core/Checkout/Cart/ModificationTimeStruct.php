<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart;

use Shopware\Core\Framework\Struct\Struct;

class ModificationTimeStruct extends Struct
{
    public const CART_EXTENSION_NAME = 'mailCampaignsAbandonedCartModificationTime';

    public function __construct(
        protected ?\DateTimeInterface $modifiedAt
    )
    {
    }

    public function getModifiedAt(): ?\DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTimeInterface $modifiedAt): void
    {
        $this->modifiedAt = $modifiedAt;
    }
}
