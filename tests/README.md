# Cart selection regression checks

After `composer install`, run against a MySQL/MariaDB test database with the PHP
`mysqli` extension enabled:

```sh
ABANDONED_CART_TEST_DB_HOST=127.0.0.1 \
ABANDONED_CART_TEST_DB_NAME=regression \
ABANDONED_CART_TEST_DB_USER=root \
ABANDONED_CART_TEST_DB_PASSWORD=secret \
php -d zend.assertions=1 -d assert.exception=1 tests/regression.php
```

The script uses real Shopware carts and Doctrine queries for both repository
handlers. All fixtures use connection-local temporary tables. Checks cover
rejected carts before valid candidates, guest login, the 100-result limit,
backlog progress, update query isolation, and the abandonment time threshold.
