<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use DateTimeInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class AbandonedCartEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $cartToken;

    /**
     * @var float
     */
    protected $price;

    /**
     * @var string
     */
    protected $lineItems;

    /**
     * @var string
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

    /**
     * @var DateTimeInterface|null
     */
    protected $updatedAt;

    /**
     * @var CustomerEntity|null
     */
    protected $customer;

    /**
     * @var SalesChannelEntity|null
     */
    protected $salesChannel;

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
        return json_decode($this->lineItems, true);
    }

    public function setLineItems(array $lineItems): void
    {
        $this->lineItems = json_encode($lineItems);
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
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

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }
}
