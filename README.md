# MailCampaigns - "Abandoned" cart plugin for Shopware 6.6
This [Shopware 6](https://www.shopware.com/en/products/shopware-6/) plugin adds an the `/abandoned-cart` API endpoint that allows you to retrieve shopping carts that your customers have left behind. 
After a configured number of seconds a cart can be considered as abandoned.
It only returns carts of active known customers, that have not been converted to an order yet. This way you can send a reminder to your customers to complete their order. 

Check the releases for support for previous versions of Shopify. 

## Getting started

Either download the .zip file and upload it in your Shopware 6 Admin panel via *Extensions > My extensions > Upload extension* or install it via Composer.

### Composer
This plugin is currently only available as a [Composer](https://getcomposer.org/) package
and can be installed with the following command:

```bash
composer require mailcampaigns/shopware-6-abandoned-cart-plugin
```

After that, run this command so Shopware knows about its existence:

```bash
bin/console plugin:refresh
```

### Activate plugin
After installation, this plugin still needs to be activated. This can be done via the **Admin** panel
(**Extensions > My extensions**) or by running the following command:

```bash
bin/console plugin:install --activate MailCampaignsAbandonedCart
```

Shopware recommends clearing the cache after running the above command:
```bash
bin/console cache:clear
```

### Configuration
To determine when a cart can be considered "abandoned" you can configure this after activating the plugin.
The value you enter is the number of seconds (default 3600, which is one hour) after a cart is created.

Make sure the settings for the carts make sense in combination with the setting in our plugin. In other words, the setting
for carts 'Time in minutes for a customer to finalize a transaction' should be longer than the setting in our plugin.

> **Note:** Abandoned carts are generated using [scheduled tasks] and therefore depend on the [message queue].

[scheduled tasks]: https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/add-scheduled-task#executing-the-scheduled-task
[message queue]: https://developer.shopware.com/docs/guides/hosting/infrastructure/message-queue
