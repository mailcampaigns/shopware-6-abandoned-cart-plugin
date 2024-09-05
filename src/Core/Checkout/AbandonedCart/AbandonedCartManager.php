<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use Doctrine\DBAL\Exception;
use MailCampaigns\AbandonedCart\Core\Checkout\Cart\CartRepository;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
final class AbandonedCartManager
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly EntityRepository $abandonedCartRepository
    ) {
    }

    /**
     * @return int The number of generated "abandoned" carts.
     * @throws Exception
     */
    public function generate(): int
    {
        $cnt = 0;

        foreach ($this->cartRepository->findMarkableAsAbandoned() as $cart) {
            $abandonedCart = AbandonedCartFactory::createFromArray($cart);

            $this->abandonedCartRepository->upsert([
                [
                    'cartToken' => $abandonedCart->getCartToken(),
                    'price' => $abandonedCart->getPrice(),
                    'lineItems' => $abandonedCart->getLineItems(),
                    'customerId' => $abandonedCart->getCustomerId(),
                ],
            ], new Context(new SystemSource()));

            $cnt++;
        }

        return $cnt;
    }

    /**
     * @return int The number of deleted "abandoned" carts.
     * @throws Exception
     */
    public function cleanUp(): int
    {
        $cnt = 0;

        foreach ($this->cartRepository->findTokensForUpdatedOrDeletedWithAbandonedCartAssociation() as $token) {
            $abandonedCartId = $this->findAbandonedCartIdByToken($token);

            if ($abandonedCartId !== null) {
                $this->abandonedCartRepository->delete([
                    [
                        'id' => $abandonedCartId,
                    ],
                ], new Context(new SystemSource()));

                $cnt++;
            }
        }

        return $cnt;
    }

    private function findAbandonedCartIdByToken(string $token): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('cartToken', $token));

        return $this->abandonedCartRepository
            ->searchIds($criteria, new Context(new SystemSource()))
            ->firstId();
    }
}
