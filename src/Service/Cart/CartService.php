<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService as CoreCartService;
use Shopware\Core\Content\Product\Cart\ProductLineItemFactory;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartService extends AbstractCartService
{
    private CoreCartService $coreCartService;

    private ProductLineItemFactory $productLineItemFactory;

    public function __construct(
        CoreCartService $coreCartService,
        ProductLineItemFactory $productLineItemFactory
    ) {
        $this->coreCartService = $coreCartService;
        $this->productLineItemFactory = $productLineItemFactory;
    }

    public function getDecorated(): AbstractCartBackupService
    {
        throw new DecorationPatternException(self::class);
    }

    public function addProductToCart(string $productId, int $quantity, Cart $directCart, SalesChannelContext $context): Cart
    {
        $productLineItem = $this->productLineItemFactory->create($productId, ['quantity' => $quantity]);

        return $this->coreCartService->add($directCart, $productLineItem, $context);
    }

    public function getCart(string $token, SalesChannelContext $context): Cart
    {
        return $this->coreCartService->getCart($token, $context);
    }
}
