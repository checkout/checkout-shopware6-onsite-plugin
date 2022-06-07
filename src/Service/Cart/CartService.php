<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Cart;

use CheckoutCom\Shopware6\Service\ContextService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService as CoreCartService;
use Shopware\Core\Content\Product\Cart\ProductLineItemFactory;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartService extends AbstractCartService
{
    private CoreCartService $coreCartService;

    private ContextService $contextService;

    private AbstractContextSwitchRoute $contextSwitchRoute;

    private ProductLineItemFactory $productLineItemFactory;

    public function __construct(
        CoreCartService $coreCartService,
        ContextService $contextService,
        AbstractContextSwitchRoute $contextSwitchRoute,
        ProductLineItemFactory $productLineItemFactory
    ) {
        $this->coreCartService = $coreCartService;
        $this->contextService = $contextService;
        $this->contextSwitchRoute = $contextSwitchRoute;
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

    /**
     * Get and Recalculate a modified shopping cart
     */
    public function recalculateCart(SalesChannelContext $context): Cart
    {
        $cart = $this->getCart($context->getToken(), $context);

        return $this->coreCartService->recalculate($cart, $context);
    }

    public function getCart(string $token, SalesChannelContext $context): Cart
    {
        return $this->coreCartService->getCart($token, $context);
    }

    public function getShippingCostsPrice(Cart $cart): float
    {
        return $cart->getDeliveries()->getShippingCosts()->sum()->getTotalPrice();
    }

    /**
     * Update country for the current context
     */
    public function updateContextCountry(SalesChannelContext $context, string $countryID): SalesChannelContext
    {
        $dataBag = new RequestDataBag();

        $dataBag->add([
            SalesChannelContextService::COUNTRY_ID => $countryID,
        ]);

        $this->contextSwitchRoute->switchContext($dataBag, $context);

        return $this->contextService->getSalesChannelContext(
            $context->getSalesChannel()->getId(),
            $context->getToken(),
        );
    }

    /**
     * Update shipping method for the current context
     */
    public function updateContextShippingMethod(SalesChannelContext $context, string $shippingMethodID): SalesChannelContext
    {
        $dataBag = new RequestDataBag();

        $dataBag->add([
            SalesChannelContextService::SHIPPING_METHOD_ID => $shippingMethodID,
        ]);

        $this->contextSwitchRoute->switchContext($dataBag, $context);

        return $this->contextService->getSalesChannelContext(
            $context->getSalesChannel()->getId(),
            $context->getToken(),
        );
    }

    /**
     * Update payment method for the current context
     */
    public function updateContextPaymentMethod(SalesChannelContext $context, string $paymentMethodId): SalesChannelContext
    {
        $dataBag = new RequestDataBag();

        $dataBag->add([
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId,
        ]);

        $this->contextSwitchRoute->switchContext($dataBag, $context);

        return $this->contextService->getSalesChannelContext(
            $context->getSalesChannel()->getId(),
            $context->getToken(),
        );
    }
}
