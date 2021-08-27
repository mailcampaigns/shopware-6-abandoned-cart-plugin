<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class CartDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cart';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CartEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CartCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('token', 'token', 50))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new StringField('name', 'name', 500))->addFlags(new ApiAware(), new Required()),
            (new LongTextField('cart', 'cart'))->addFlags(new ApiAware(), new Required()),
            (new FloatField('price', 'price'))->addFlags(new ApiAware(), new Required()),
            (new IntField('line_item_count', 'lineItemCount'))->addFlags(new ApiAware(), new Required()),
            (new FkField('currency_id', 'currencyId', CurrencyDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('shipping_method_id', 'shippingMethodId', ShippingMethodDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('payment_method_id', 'paymentMethodId', PaymentMethodDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('country_id', 'countryId', CountryDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new ApiAware()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new CreatedAtField())->addFlags(new ApiAware(), new Required()),
        ]);

        return new FieldCollection([
            (new StringField('token', 'token', 50))->addFlags(new ApiAware(), new PrimaryKey(), new Required(), new WriteProtected()),
            (new StringField('name', 'name', 500))->addFlags(new ApiAware(), new Required(), new WriteProtected()),
            (new LongTextField('cart', 'cart'))->addFlags(new ApiAware(), new Required(), new WriteProtected()),
            (new FloatField('price', 'price'))->addFlags(new ApiAware(), new Required(), new WriteProtected()),
            (new IntField('line_item_count', 'lineItemCount'))->addFlags(new ApiAware(), new Required(), new WriteProtected()),
            (new FkField('currency_id', 'currencyId', CurrencyDefinition::class))->addFlags(new ApiAware(), new Required(), new WriteProtected()),
            (new FkField('shipping_method_id', 'shippingMethodId', ShippingMethodDefinition::class))->addFlags(new ApiAware(), new Required(), new WriteProtected()),
            (new FkField('payment_method_id', 'paymentMethodId', PaymentMethodDefinition::class))->addFlags(new ApiAware(), new Required(), new WriteProtected()),
            (new FkField('country_id', 'countryId', CountryDefinition::class))->addFlags(new ApiAware(), new Required(), new WriteProtected()),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new ApiAware(), new WriteProtected()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new ApiAware(), new Required(), new WriteProtected()),
            (new CreatedAtField())->addFlags(new ApiAware(), new Required(), new WriteProtected()),
        ]);
    }
}
