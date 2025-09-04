# 🛒 Abandoned Cart Plugin for Shopware 6 | [MailCampaigns](https://www.mailcampaigns.nl)


![Shopware 6](https://img.shields.io/badge/Shopware-6.x-blue?logo=shopware)
![Plugin Version](https://img.shields.io/github/v/release/mailcampaigns/shopware-6-abandoned-cart-plugin)
![License](https://img.shields.io/github/license/mailcampaigns/shopware-6-abandoned-cart-plugin)

This [Shopware 6](https://www.shopware.com/en/products/shopware-6/) plugin adds an `/abandoned-cart` API endpoint that allows you to retrieve shopping carts that your customers have left behind.
> **ℹ️ Note:** By default, Shopware stores shopping carts in the SQL database. For high-throughput scenarios, Shopware supports storing carts in Redis instead of SQL. If your Shopware installation is configured to use Redis for cart storage, this plugin may not be compatible, as it expects carts to be stored in the database. Learn more: [Shopware Cart Storage](https://developer.shopware.com/docs/guides/hosting/performance/cart-storage.html)

After a configured number of **seconds**, a cart can be considered abandoned.  
It only returns carts of **active known customers** that have **not been converted to an order** yet.

Use this data to send automated cart recovery reminders to increase your conversions.

---

## 🎯 Features

- 🔗 Adds `/abandoned-cart` API endpoint
- 👤 Only returns known, non-order customers
- ⏱️ Configurable timeout in seconds (default: 3600)
- 🛠️ Compatible with scheduled tasks & message queue
- ✅ Supports Shopware 6.4 → 6.7

---

## 🛍️ Supported Shopware Versions

| Shopware Version | Plugin Version | Download |
|------------------|----------------|----------|
| 6.4              | 1.7.1          | [🔗 View Release](https://github.com/mailcampaigns/shopware-6-abandoned-cart-plugin/releases/tag/1.7.1) |
| 6.5, 6.6, 6.7    | 3.0.9          | [🔗 View Release](https://github.com/mailcampaigns/shopware-6-abandoned-cart-plugin/releases/tag/3.0.9) |

---

## 🚀 Getting Started

You can either upload the `.zip` manually or install via Composer.

### 🔌 Composer Installation

```bash
composer require mailcampaigns/shopware-6-abandoned-cart-plugin
bin/console plugin:refresh
```

### 🔄 Activate Plugin

**Option 1 (Admin panel):**  
Go to `Extensions > My extensions` and activate the plugin.

**Option 2 (CLI):**
```bash
bin/console plugin:install --activate MailCampaignsAbandonedCart
bin/console cache:clear
```

## 🔧 Configuration

After activating, configure the cart timeout setting:  
> "Number of seconds after which a cart is considered abandoned" (default: `3600`)

Make sure this timeout is **less than** than Shopware's own cart expiration setting:  
> `Time in minutes for a customer to finalize a transaction`

Make sure to give the API user the necessary permissions to access the `/abandoned-cart` endpoint.
- `abandoned_cart:read` permission to **read** abandoned carts

> 🧠 Abandoned carts are generated using [scheduled tasks] and depend on the [message queue].

[scheduled tasks]: https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/add-scheduled-task#executing-the-scheduled-task  
[message queue]: https://developer.shopware.com/docs/guides/hosting/infrastructure/message-queue

---

## ⚙️ Example API Request
### 🔍 Retrieve Abandoned Carts
```http
GET /api/abandoned-cart
```
Optional query parameters:
- `limit` (default: 10)
- `page` (default: 1)

```http
GET /api/abandoned-cart?limit=5&page=1
```

## Example response:
```
{
    "data": [
        {
            "id": "0195cd19f28e7327a24222075a312f39",
            "type": "abandoned_cart",
            "attributes": {
                "cartToken": "aeyBUILGpDFdJQS77WMkQVn6wXgOHgC6",
                "price": 40.0,
                "lineItems": [
                    {
                        "id": "3ac014f329884b57a2cce5a29f34779c",
                        "good": true,
                        "type": "product",
                        "label": "Main product, free shipping with highlighting",
                        "quantity": 2,
                        ...
                    }
                ],
                "customerId": "0195cd14000773d7b6697f79bae4283d",
                "createdAt": "2025-03-25T11:39:44.661+00:00",
                "updatedAt": "2025-03-25T15:28:34.982+00:00",
                ...
            },
            ...
        }
    ],
    ...
}
```
### 🔍 Retrieve a Specific Abandoned Cart

You can retrieve details of a specific abandoned cart using its unique `{id}`.

#### Endpoint:
```http
GET /api/abandoned-cart/{id}
```

#### Method:
- **GET**: Retrieve details of a specific abandoned cart.

## 📡 Events

The plugin dispatches several events that you can listen to in your custom code:

### AfterCartMarkedAsAbandonedEvent
Dispatched when a cart is marked as abandoned. Contains:
- `AbandonedCartEntity`: Newly created abandoned cart entity
- `array`: Original Shopware cart data
- `Context`: Shopware context

### AfterAbandonedCartUpdatedEvent
Dispatched when an abandoned cart is updated. Contains:
- `AbandonedCartEntity`: Updated abandoned cart entity
- `array`: Updated Shopware cart data
- `Context`: Shopware context

---

## 🤝 Contributing

Pull requests, issues, and feedback are welcome.  
For support, contact us at [support@mailcampaigns.nl](mailto:support@mailcampaigns.nl)

---

## 🔗 More

Check all releases:  
👉 [https://github.com/mailcampaigns/shopware-6-abandoned-cart-plugin/releases](https://github.com/mailcampaigns/shopware-6-abandoned-cart-plugin/releases)

Check out this handy database seeder for seeding test customers and data in your development environment:  
👉 https://github.com/m-a-x-s-e-e-l-i-g/shopware-6-cart-seeder

Check out Dockware for easily setting up a local Shopware environment:  
👉 https://docs.dockware.io/use-dockware/first-run

