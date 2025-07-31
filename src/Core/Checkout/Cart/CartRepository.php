<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\Cart;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\Cart\Handler\CartRepositoryHandlerFactory;
use MailCampaigns\AbandonedCart\Core\Checkout\Cart\Handler\CartRepositoryHandlerInterface;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
final class CartRepository
{
    private readonly CartRepositoryHandlerInterface $handler;

    public function __construct(
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService,
        CartRepositoryHandlerFactory $handlerFactory,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {
        $this->handler = $handlerFactory->createHandler();
    }

    /**
     * Finds and returns an array of `cart` records that are considered "abandoned" and meet specific criteria.
     * 
     * This method performs the following steps:
     * 1. Retrieves cart records that are not marked as abandoned OR have been updated after being marked as abandoned. Depending on the value of the $retrieveUpdated parameter.
     * 2. Filters carts to include only those with a customer ID.
     * 3. Excludes carts marked for recalculation.
     * 4. Adds customer ID, total price, and line item count to each cart.
     * 5. Removes carts associated with inactive customers.
     * 6. Removes carts for customers who have placed an order after the cart was created.
     * 
     * @param bool $retrieveUpdated Optional parameter to specify whether to retrieve updated abandoned carts.
     *                              If true, retrieves updated abandoned carts. If false, retrieves new abandoned carts.
     *                              Default is false.
     * @return array An array of abandoned cart records with additional details.
     * @throws Exception
     */
    public function findAbandonedCartsWithCriteria(bool $retrieveUpdated = false): array
    {
        $abandonedCartTokens = $this->getAbandonedCartTokens();
        if (count($abandonedCartTokens) === 0) {
            return [];
        }

        $data = $this->handler->buildAbandonedCartsQuery($abandonedCartTokens, $retrieveUpdated);

        foreach($data as $key => $row) {
            try {
                $cartObj = !empty($row['compressed']) 
                    ? CacheValueCompressor::uncompress($row['payload']) 
                    : unserialize((string) $row['payload']);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to unserialize cart payload.', ['exception' => $e]);
                $cartObj = null;
            }

            if (!$cartObj instanceof \Shopware\Core\Checkout\Cart\Cart) {
                $this->logger->error('Invalid cart object.', ['cart' => $row['payload']]);
                unset($data[$key]);
                continue;
            }

            // Remove carts that are marked as recalculated since they can be considered as garbage
            if($cartObj->getBehavior()->isRecalculation()) {
                unset($data[$key]);
                continue;
            }

            // Process cart row with version-specific handler
            $processedRow = $this->handler->processCartRow($row, $cartObj, $retrieveUpdated);
            if ($processedRow === null) {
                unset($data[$key]);
                continue;
            }
            
            $data[$key] = $processedRow;

            // Remove carts of customers that have placed an order after the cart was created
            if ($this->hasCustomerPlacedOrderAfterCart($data[$key])) {
                unset($data[$key]);
                continue;
            }
        }

        $this->logger->debug(count($data) . ' abandoned carts found.');

        return $data;
    }

    /**
     * Obtains tokens of carts that are considered abandoned.
     *
     * This function constructs an SQL query that selects the most recent cart token
     * for each customer whose cart has been abandoned. A cart is considered abandoned
     * if it was created before a certain time threshold, which is determined by the
     * 'MailCampaignsAbandonedCart.config.markAbandonedAfter' configuration setting.
     *
     * @return array<string> Returns an array of cart tokens that are considered abandoned.
     */
    private function getAbandonedCartTokens(): array
    {
        $considerAbandonedAfter = (new DateTime())->modify(sprintf(
            '-%d seconds',
            $this->systemConfigService->get('MailCampaignsAbandonedCart.config.markAbandonedAfter')
        ));

        $sql = $this->handler->getAbandonedCartTokensQuery($considerAbandonedAfter);
        $statement = $this->connection->prepare($sql);

        return $statement->executeQuery()->fetchFirstColumn();
    }

    /**
     * Check if customer has placed an order after the cart was created
     */
    private function hasCustomerPlacedOrderAfterCart(array $cartData): bool
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('oc.id')
            ->from('order_customer', 'oc')
            ->leftJoin('oc', 'customer', 'c', 'oc.customer_id = c.id')
            ->where($qb->expr()->eq('oc.customer_id', ':customerId'))
            ->andWhere($qb->expr()->gte('oc.created_at', ':cartCreatedAt'))
            ->setParameter('customerId', $cartData['customer_id'])
            ->setParameter('cartCreatedAt', $cartData['created_at']);

        $result = $qb->executeQuery()->fetchOne();
        return $result !== false;
    }
}
