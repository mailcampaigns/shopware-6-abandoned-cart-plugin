<?php declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Core\Checkout\AbandonedCart;

use MailCampaigns\AbandonedCart\Core\Checkout\Cart\CartRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class AbandonedCartManager
{
    private CartRepository $cartRepository;
    private EntityRepositoryInterface $abandonedCartRepository;

    public function __construct(
        CartRepository $cartRepository,
        EntityRepositoryInterface $abandonedCartRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->abandonedCartRepository = $abandonedCartRepository;
    }

    /**
     * @return int The number of generated "abandoned" carts.
     */
    public function generate(): int
    {
        $cnt = 0;

        foreach ($this->cartRepository->findMarkableAsAbandoned() as $cart) {
            $abandonedCart = AbandonedCartFactory::createFromArray($cart);

            $this->abandonedCartRepository->create([
                [
                    'cartToken' => $abandonedCart->getCartToken(),
                    'price' => $abandonedCart->getPrice(),
                    'lineItems' => $abandonedCart->getLineItems(),
                    'customerId' => $abandonedCart->getCustomerId(),
                    'salesChannelId' => $abandonedCart->getSalesChannelId(),
                ],
            ], Context::createDefaultContext());

            $cnt++;
        }

        return $cnt;
    }

    /**
     * @return int The number of deleted "abandoned" carts.
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
                ], Context::createDefaultContext());

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
            ->searchIds($criteria, Context::createDefaultContext())
            ->firstId();
    }
}
