<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use MailCampaigns\AbandonedCart\Exception\InvalidCartDataException;
use MailCampaigns\AbandonedCart\Exception\MissingCartDataException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class AbandonedCartFactory
{
    /**
     * @var string[]
     */
    private static array $requiredValues = [
        'token',
        'price',
        'payload',
        'customer_id',
    ];

    /**
     * @throws MissingCartDataException if a required value is missing.
     * @throws InvalidCartDataException if the given 'cart' value is not an instance of {@see Cart}.
     */
    public static function createFromArray(array $data): AbandonedCartEntity
    {
        self::validateData($data);

        try {
            $cart = !empty($data['compressed']) ? CacheValueCompressor::uncompress($data['payload']) : unserialize((string) $data['payload']);
        } catch (\Throwable $e) {
            $cart = null;
        }

        if (!$cart instanceof Cart) {
            throw new InvalidCartDataException('cart', Cart::class, $cart);
        }

        $entity = new AbandonedCartEntity();
        $entity->setCartToken($data['token']);
        $entity->setPrice((float)$data['price']);
        $entity->setLineItems($cart->getLineItems()->jsonSerialize());
        $entity->setCustomerId($data['customer_id']);

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
