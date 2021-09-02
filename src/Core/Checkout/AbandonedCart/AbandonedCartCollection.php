<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 *
 * @method void                     add(AbandonedCartEntity $entity)
 * @method void                     set(string $key, AbandonedCartEntity $entity)
 * @method AbandonedCartEntity[]    getIterator()
 * @method AbandonedCartEntity[]    getElements()
 * @method AbandonedCartEntity|null get(string $key)
 * @method AbandonedCartEntity|null first()
 * @method AbandonedCartEntity|null last()
 */
class AbandonedCartCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AbandonedCartEntity::class;
    }
}
