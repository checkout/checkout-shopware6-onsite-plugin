<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\Cart;

use Cko\Shopware6\Exception\DirectCartInvalidException;
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

    public function copyOriginCartToCartContext(SalesChannelContext $context): Cart
    {
        // Get the origin cart from the token
        $originCart = $this->coreCartService->getCart($this->getBackupCartTokenKey($context), $context);

        // Copy to the cart context because we pass the context token as the target cart token
        return $this->cloneCartAndSave($originCart, $context->getToken(), $context);
    }

    public function copyDirectCartToCartContext(string $directCartToken, SalesChannelContext $context): Cart
    {
        // Get the direct cart from the token
        $directCart = $this->coreCartService->getCart($directCartToken, $context);

        if ($directCart->getLineItems()->count() === 0) {
            // The line items can be empty because the cart has already added products to it
            throw new DirectCartInvalidException($directCartToken, [
                'message' => 'The line items in the direct cart are empty.',
            ]);
        }

        // Copy to the cart context because we pass the context token as the target cart token
        return $this->cloneCartAndSave($directCart, $context->getToken(), $context);
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
