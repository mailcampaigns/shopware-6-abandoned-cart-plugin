<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
final class AbandonedCartEntity extends Entity
{
    use EntityIdTrait;

    protected string $cartToken;
    protected float $price;
    protected array $lineItems;
    protected string $customerId;
    protected ?CustomerEntity $customer;

    public function getCartToken(): string
    {
        return $this->cartToken;
    }

    public function setCartToken(string $token): void
    {
        $this->cartToken = $token;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function setLineItems(array $lineItems): void
    {
        $this->lineItems = $lineItems;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    /** @noinspection PhpUnused */
    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }
}
