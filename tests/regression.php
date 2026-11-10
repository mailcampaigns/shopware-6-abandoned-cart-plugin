<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use MailCampaigns\AbandonedCart\Core\Checkout\Cart\CartRepository;
use MailCampaigns\AbandonedCart\Core\Checkout\Cart\Handler\CartRepositoryHandlerFactory;
use MailCampaigns\AbandonedCart\Service\ShopwareVersionHelper;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SystemConfig\SystemConfigService;

require dirname(__DIR__) . '/vendor/autoload.php';

if ((int) ini_get('zend.assertions') !== 1 || !getenv('ABANDONED_CART_TEST_DB_HOST')) {
    throw new RuntimeException('Enable zend.assertions=1 and set ABANDONED_CART_TEST_DB_HOST.');
}

class RegressionCustomer extends Struct
{
    public function __construct(private readonly string $customerId) {}

    public function getCustomerId(): string
    {
        return $this->customerId;
    }
}

$connection = DriverManager::getConnection([
    'driver' => 'mysqli',
    'host' => getenv('ABANDONED_CART_TEST_DB_HOST'),
    'port' => (int) (getenv('ABANDONED_CART_TEST_DB_PORT') ?: 3306),
    'user' => getenv('ABANDONED_CART_TEST_DB_USER') ?: 'root',
    'password' => getenv('ABANDONED_CART_TEST_DB_PASSWORD') ?: '',
    'dbname' => getenv('ABANDONED_CART_TEST_DB_NAME') ?: 'regression',
]);

// Temporary tables shadow real tables and disappear when the connection closes.
$connection->executeStatement('CREATE TEMPORARY TABLE cart (
    token VARCHAR(50) PRIMARY KEY, payload LONGBLOB, compressed TINYINT DEFAULT 0,
    created_at DATETIME(3), updated_at DATETIME(3), customer_id BINARY(16),
    price FLOAT DEFAULT 10, line_item_count INT DEFAULT 1
)');
$connection->executeStatement('CREATE TEMPORARY TABLE customer (id BINARY(16) PRIMARY KEY, active TINYINT)');
$connection->executeStatement('CREATE TEMPORARY TABLE abandoned_cart (
    id BINARY(16) PRIMARY KEY, cart_token VARCHAR(50), created_at DATETIME(3), updated_at DATETIME(3)
)');
$connection->executeStatement('CREATE TEMPORARY TABLE order_customer (
    id BINARY(16), customer_id BINARY(16), created_at DATETIME(3)
)');

$seed = static function (string $token, bool $guest = false, bool $invalid = false, bool $active = true, bool $compressed = false) use ($connection): void {
    $cart = new Cart($token);
    $cart->setBehavior(new CartBehavior());
    $customerId = md5($token);
    if (!$guest) {
        $cart->addExtension('regression-customer', new RegressionCustomer($customerId));
        $connection->insert('customer', ['id' => hex2bin($customerId), 'active' => (int) $active]);
    }
    $connection->insert('cart', [
        'token' => $token,
        'payload' => $compressed ? CacheValueCompressor::compress($cart) : serialize($invalid ? new stdClass() : $cart),
        'compressed' => (int) $compressed,
        'created_at' => '2020-07-01 00:00:00.000',
        'updated_at' => '2020-07-02 00:00:00.000',
        'customer_id' => $guest ? null : hex2bin($customerId),
    ]);
};
$mark = static function (string $token, ?string $updatedAt = null) use ($connection): void {
    $connection->insert('abandoned_cart', [
        'id' => hex2bin(md5($token)), 'cart_token' => $token,
        'created_at' => '2020-01-01 00:00:00.000', 'updated_at' => $updatedAt,
    ]);
};
$reset = static function () use ($connection): void {
    foreach (['abandoned_cart', 'cart', 'customer', 'order_customer'] as $table) {
        $connection->executeStatement('DELETE FROM ' . $table);
    }
};
$config = new class extends SystemConfigService {
    public function __construct() {}

    public function get(string $key, ?string $salesChannelId = null): int
    {
        return 3600;
    }
};

foreach (['6.5', '6.6', '6.7'] as $version) {
    $versionHelper = new class($version) extends ShopwareVersionHelper {
        public function __construct(private readonly string $version) {}

        public function getMajorMinorShopwareVersion(): string
        {
            return $this->version;
        }
    };
    $factory = new CartRepositoryHandlerFactory($connection, $versionHelper, new NullLogger());
    $repository = new CartRepository($connection, $config, $factory, new NullLogger());
    $reset();
    assert($repository->findAbandonedCartsWithCriteria() === []);
    assert($repository->findAbandonedCartsWithCriteria(true) === []);

    // Guests, invalid payloads and inactive customers cannot hide later valid carts.
    for ($i = 0; $i < 150; $i++) {
        $seed(sprintf('a-guest-%03d', $i), true, false, true, $i % 2 === 0);
        $seed(sprintf('b-invalid-%03d', $i), false, true);
    }
    $seed('c-inactive', false, false, false);
    $seed('z-valid');
    $seed('z-valid-compressed', false, false, true, true);
    $rows = $repository->findAbandonedCartsWithCriteria();
    assert(array_column($rows, 'token') === ['z-valid', 'z-valid-compressed']);

    // A rejected guest remains eligible after logging in; no permanent skip marker.
    $cart = new Cart('a-guest-000');
    $cart->setBehavior(new CartBehavior());
    $customerId = md5('a-guest-000');
    $cart->addExtension('regression-customer', new RegressionCustomer($customerId));
    $connection->insert('customer', ['id' => hex2bin($customerId), 'active' => 1]);
    $connection->update('cart', ['payload' => serialize($cart), 'compressed' => 0, 'customer_id' => hex2bin($customerId)], ['token' => 'a-guest-000']);
    assert(array_column($repository->findAbandonedCartsWithCriteria(), 'token') === ['a-guest-000', 'z-valid', 'z-valid-compressed']);

    // The limit applies to accepted carts, and subsequent runs drain the backlog.
    $reset();
    for ($i = 0; $i < 150; $i++) {
        $seed(sprintf('valid-%03d', $i));
    }
    $first = $repository->findAbandonedCartsWithCriteria();
    assert(count($first) === 100);
    assert($first[0]['token'] === 'valid-000' && $first[99]['token'] === 'valid-099');
    foreach ($first as $row) {
        $mark($row['token']);
    }
    $second = $repository->findAbandonedCartsWithCriteria();
    assert(count($second) === 50 && $second[0]['token'] === 'valid-100');
    foreach ($second as $row) {
        $mark($row['token']);
    }
    assert($repository->findAbandonedCartsWithCriteria() === []);

    // Update runs have the same accepted-cart limit and progress across batches.
    $first = $repository->findAbandonedCartsWithCriteria(true);
    assert(count($first) === 100);
    foreach ($first as $row) {
        $connection->update('abandoned_cart', ['updated_at' => '2021-01-01 00:00:00.000'], ['cart_token' => $row['token']]);
    }
    $second = $repository->findAbandonedCartsWithCriteria(true);
    assert(count($second) === 50 && $second[0]['token'] === 'valid-100');

    // An OR in the update query must not escape the supplied token batch.
    $reset();
    $seed('a-requested');
    $seed('b-outside-batch');
    $mark('a-requested');
    $mark('b-outside-batch', '2020-02-01 00:00:00.000');
    assert(array_column($factory->createHandler()->buildAbandonedCartsQuery(['a-requested'], true), 'token') === ['a-requested']);

    // New carts must not consume the update limit before a marked cart.
    $reset();
    for ($i = 0; $i < 150; $i++) {
        $seed(sprintf('a-new-%03d', $i));
    }
    $seed('z-updated');
    $mark('z-updated');
    assert(array_column($repository->findAbandonedCartsWithCriteria(true), 'token') === ['z-updated']);
    $connection->executeStatement("UPDATE cart SET created_at = '2099-01-01', updated_at = '2099-01-02' WHERE token = 'z-updated'");
    assert($repository->findAbandonedCartsWithCriteria(true) === []);

    echo "Shopware $version handler: all regression checks passed.\n";
}
