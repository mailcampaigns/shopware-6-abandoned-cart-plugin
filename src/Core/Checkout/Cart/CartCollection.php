<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 *
 * @method void            add(CartEntity $entity)
 * @method void            set(string $key, CartEntity $entity)
 * @method CartEntity[]    getIterator()
 * @method CartEntity[]    getElements()
 * @method CartEntity|null get(string $key)
 * @method CartEntity|null first()
 * @method CartEntity|null last()
 */
class CartCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CartEntity::class;
    }
}
