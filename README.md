# MailCampaigns - "Abandoned" cart plugin
This [Shopware 6](https://www.shopware.com/en/products/shopware-6/) plugin allows you to automatically mark carts as
"abandoned" after a configured number of seconds. Currently, the "abandoned" carts are only available through the
custom */abandoned-cart* API endpoints.

## Getting started

### Composer
This plugin is currently only available as a [Composer](https://getcomposer.org/) package
and can be installed with the following command:

```bash
$ composer require "mailcampaigns/shopware-6-abandoned-cart-plugin"
```

### Activate plugin
After installation, this plugin still needs to be activated. This can be done via the **Admin** panel
(**Extensions > My extensions**) or by running the following command:

```bash
$ bin/console plugin:install --activate MailCampaignsAbandonedCart
```

### Configuration
To determine when a cart can be considered "abandoned" you can configure this after activating the plugin.
The value you enter is the number of seconds (default 3600) after a cart is created or updated.

> **Note:** Abandoned carts are generated using [scheduled tasks] and therefore depend on the [message queue].

[scheduled tasks]: https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/add-scheduled-task#executing-the-scheduled-task
[message queue]: https://developer.shopware.com/docs/guides/hosting/infrastructure/message-queue
