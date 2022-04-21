<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartPersisterInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService as CoreCartService;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartBackupService extends AbstractCartBackupService
{
    private CoreCartService $coreCartService;

    private CartPersisterInterface $cartPersist;

    public function __construct(CoreCartService $coreCartService, CartPersisterInterface $cartPersist)
    {
        $this->coreCartService = $coreCartService;
        $this->cartPersist = $cartPersist;
    }

    public function getDecorated(): AbstractCartBackupService
    {
        throw new DecorationPatternException(self::class);
    }

    public function createNewDirectTokenCart(SalesChannelContext $context): Cart
    {
        $newToken = Uuid::randomHex();
        $saleChannelName = $context->getSalesChannel()->getName() ?? CoreCartService::SALES_CHANNEL;

        $directCart = $this->coreCartService->createNew($newToken, $saleChannelName);

        // Recalculate the cart and persist it
        return $this->coreCartService->recalculate($directCart, $context);
    }

    public function deleteCart(string $token, SalesChannelContext $context): void
    {
        $this->cartPersist->delete($token, $context);
    }

    public function getBackupCartTokenKey(SalesChannelContext $context): string
    {
        return sprintf('%s_%s', static::ORIGIN_CART_TOKEN, $context->getToken());
    }

    /**
     * Clone the cart and save by the given token
     */
    public function cloneCartAndSave(Cart $sourceCart, string $targetToken, SalesChannelContext $context): Cart
    {
        $saleChannelName = $context->getSalesChannel()->getName() ?? CoreCartService::SALES_CHANNEL;

        // create a new cart with source cart's data (to avoid foreign reference problems)
        $targetCart = $this->coreCartService->createNew($targetToken, $saleChannelName);

        // Set the items
        $targetCart->setLineItems($sourceCart->getLineItems());

        $this->coreCartService->setCart($targetCart);

        // Recalculate the cart and persist it
        return $this->coreCartService->recalculate($targetCart, $context);
    }
}
