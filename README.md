# 🛒 MailCampaigns - "Abandoned" Cart Plugin for Shopware 6

![Shopware 6](https://img.shields.io/badge/Shopware-6.x-blue?logo=shopware)
![Plugin Version](https://img.shields.io/github/v/release/mailcampaigns/shopware-6-abandoned-cart-plugin)
![License](https://img.shields.io/github/license/mailcampaigns/shopware-6-abandoned-cart-plugin)

This [Shopware 6](https://www.shopware.com/en/products/shopware-6/) plugin adds an `/abandoned-cart` API endpoint that allows you to retrieve shopping carts that your customers have left behind.

After a configured number of **seconds**, a cart can be considered abandoned.  
It only returns carts of **active known customers** that have **not been converted to an order** yet.

Use this data to send automated cart recovery reminders to increase your conversions.

---

## 🎯 Features

- 🔗 Adds `/abandoned-cart` API endpoint
- 👤 Only returns known, non-order customers
- ⏱️ Configurable timeout in seconds (default: 3600)
- 🛠️ Compatible with scheduled tasks & message queue
- ✅ Supports Shopware 6.4 → 6.6

---

## 🛍️ Supported Shopware Versions

| Shopware Version | Plugin Version | Download |
|------------------|----------------|----------|
| 6.4              | 1.7.1          | [🔗 View Release](https://github.com/mailcampaigns/shopware-6-abandoned-cart-plugin/releases/tag/1.7.1) |
| 6.5              | 3.0.1          | [🔗 View Release](https://github.com/mailcampaigns/shopware-6-abandoned-cart-plugin/releases/tag/3.0.1) |
| 6.6              | 3.0.1          | [🔗 View Release](https://github.com/mailcampaigns/shopware-6-abandoned-cart-plugin/releases/tag/3.0.1) |

> ✅ Note: Plugin version `3.0.1` supports both Shopware `6.5` and `6.6`.

---

## ⚙️ Example API Request

```http
GET /store-api/abandoned-cart?limit=10
```

## Example response:
```
[
  {
    "customerId": "ab1c-23d4...",
    "cart": { ... },
    "createdAt": "2024-01-01T12:00:00Z"
  },
  ...
]
```

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

Make sure this timeout is **shorter** than Shopware's own cart expiration setting:  
> `Time in minutes for a customer to finalize a transaction`

> 🧠 Abandoned carts are generated using [scheduled tasks] and depend on the [message queue].

[scheduled tasks]: https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/add-scheduled-task#executing-the-scheduled-task  
[message queue]: https://developer.shopware.com/docs/guides/hosting/infrastructure/message-queue

---

## 📦 Release Overview

| Plugin Version | Compatible Shopware Versions |
|----------------|-------------------------------|
| 1.7.1          | 6.4                           |
| 2.0.0          | 6.5                           |
| 3.0.1          | 6.5, 6.6                      |

---

## 🤝 Contributing

Pull requests, issues, and feedback are welcome.  
For support, contact us at [support@mailcampaigns.nl](mailto:support@mailcampaigns.nl)

---

## 🔗 More

Check all releases:  
👉 [https://github.com/mailcampaigns/shopware-6-abandoned-cart-plugin/releases](https://github.com/mailcampaigns/shopware-6-abandoned-cart-plugin/releases)
