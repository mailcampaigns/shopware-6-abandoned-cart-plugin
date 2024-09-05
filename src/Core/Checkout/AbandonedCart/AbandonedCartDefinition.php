<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
final class AbandonedCartDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'abandoned_cart';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return AbandonedCartEntity::class;
    }

    public function getCollectionClass(): string
    {
        return AbandonedCartCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new StringField('cart_token', 'cartToken', 50))->addFlags(new ApiAware(), new Required()),
            (new FloatField('price', 'price'))->addFlags(new ApiAware(), new Required()),
            (new JsonField('line_items', 'lineItems'))->addFlags(new ApiAware(), new Required()),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id'),
        ]);
    }
}
