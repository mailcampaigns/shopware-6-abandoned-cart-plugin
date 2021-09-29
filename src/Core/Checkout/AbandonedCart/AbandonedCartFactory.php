<?php

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use MailCampaigns\AbandonedCart\Exception\InvalidCartDataException;
use MailCampaigns\AbandonedCart\Exception\MissingCartDataException;
use Shopware\Core\Checkout\Cart\Cart;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class AbandonedCartFactory
{
    /**
     * @var string[]
     */
    private static $requiredValues = [
        'token',
        'price',
        'cart',
        'customer_id',
        'sales_channel_id',
    ];

    /**
     * @throws MissingCartDataException if a required value is missing.
     * @throws InvalidCartDataException if the given 'cart' value is not an instance of {@see Cart}.
     */
    public static function createFromArray(array $data): AbandonedCartEntity
    {
        self::validateData($data);

        $cart = unserialize($data['cart']);

        if (!$cart instanceof Cart) {
            throw new InvalidCartDataException('cart', Cart::class, $cart);
        }

        $entity = new AbandonedCartEntity();
        $entity->setCartToken($data['token']);
        $entity->setPrice($data['price']);
        $entity->setLineItems($cart->getLineItems()->jsonSerialize());
        $entity->setCustomerId($data['customer_id']);
        $entity->setSalesChannelId($data['sales_channel_id']);

        return $entity;
    }

    /**
     * @throws MissingCartDataException if a required value is missing.
     */
    private static function validateData(array $data): void
    {
        foreach (self::$requiredValues as $requiredValue) {
            if (array_key_exists($requiredValue, $data) === false) {
                throw new MissingCartDataException($requiredValue);
            }
        }
    }
}
