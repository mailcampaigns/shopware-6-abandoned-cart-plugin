<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart;

use DateTimeInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class CartEntity extends Entity
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $cart;

    /**
     * @var float
     */
    protected $price;

    /**
     * @var int
     */
    protected $lineItemCount;

    /**
     * @var string
     */
    protected $currencyId;

    /**
     * @var string
     */
    protected $shippingMethodId;

    /**
     * @var string
     */
    protected $paymentMethodId;

    /**
     * @var string
     */
    protected $countryId;

    /**
     * @var string|null
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var DateTimeInterface
     */
    protected $createdAt;

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCart(): Cart
    {
        return unserialize($this->cart, [
            'allowed_classes' => [Cart::class],
        ]);
    }

    public function setCart(Cart $cart): void
    {
        $this->cart = serialize($cart);
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getLineItemCount(): int
    {
        return $this->lineItemCount;
    }

    public function setLineItemCount(int $lineItemCount): void
    {
        $this->lineItemCount = $lineItemCount;
    }

    public function getCurrencyId(): string
    {
        return $this->currencyId;
    }

    public function setCurrencyId(string $currencyId): void
    {
        $this->currencyId = $currencyId;
    }

    public function getShippingMethodId(): string
    {
        return $this->shippingMethodId;
    }

    public function setShippingMethodId(string $shippingMethodId): void
    {
        $this->shippingMethodId = $shippingMethodId;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function getCountryId(): string
    {
        return $this->countryId;
    }

    public function setCountryId(string $countryId): void
    {
        $this->countryId = $countryId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
