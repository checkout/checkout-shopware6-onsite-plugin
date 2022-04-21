<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Facade;

use CheckoutCom\Shopware6\Service\Cart\AbstractCartBackupService;
use CheckoutCom\Shopware6\Service\Cart\AbstractCartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Handle business logic of the direct pay
 */
class DirectPayFacade
{
    private AbstractCartService $cartService;

    private AbstractCartBackupService $cartBackupService;

    public function __construct(
        AbstractCartService $cartService,
        AbstractCartBackupService $cartBackupService
    ) {
        $this->cartService = $cartService;
        $this->cartBackupService = $cartBackupService;
    }

    /**
     * When a product is added to the cart, it will back up the original cart
     * Also create a new cart to add products to this cart (direct cart)
     * Basically, we will have 3 carts:
     *  - Current process cart (All payment/calculate/... will process in this cart)
     *  - Backup original cart
     *  - Our own cart (direct cart)
     */
    public function addProductToCart(string $productId, int $productQuantity, SalesChannelContext $context): Cart
    {
        $originalCart = $this->cartService->getCart($context->getToken(), $context);

        // Backup original cart using own cart token key
        $this->cartBackupService->cloneCartAndSave(
            $originalCart,
            $this->cartBackupService->getBackupCartTokenKey($context),
            $context
        );

        $directCart = $this->cartBackupService->createNewDirectTokenCart($context);

        // add new product to direct cart
        return $this->cartService->addProductToCart($productId, $productQuantity, $directCart, $context);
    }

    /**
     * Clear all the back-up carts
     */
    public function removeBackupCarts(?string $directCartToken, SalesChannelContext $context): void
    {
        if (!empty($directCartToken)) {
            $this->cartBackupService->deleteCart($directCartToken, $context);
        }

        $this->cartBackupService->deleteCart($this->cartBackupService->getBackupCartTokenKey($context), $context);
    }
}
