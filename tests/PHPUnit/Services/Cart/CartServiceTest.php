<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Cart;

use CheckoutCom\Shopware6\Service\Cart\CartService;
use CheckoutCom\Shopware6\Service\ContextService;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService as CoreCartService;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Product\Cart\ProductLineItemFactory;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartServiceTest extends TestCase
{
    use ContextTrait;

    /**
     * @var MockObject|CoreCartService
     */
    private $coreCartService;

    /**
     * @var ContextService|MockObject
     */
    private $contextService;

    /**
     * @var MockObject|ContextSwitchRoute
     */
    private $contextSwitchRoute;

    /**
     * @var MockObject|ProductLineItemFactory
     */
    private $productLineItemFactory;

    private CartService $cartService;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->coreCartService = $this->createMock(CoreCartService::class);
        $this->contextService = $this->createMock(ContextService::class);
        $this->contextSwitchRoute = $this->createMock(ContextSwitchRoute::class);
        $this->productLineItemFactory = $this->createMock(ProductLineItemFactory::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->cartService = new CartService(
            $this->coreCartService,
            $this->contextService,
            $this->contextSwitchRoute,
            $this->productLineItemFactory
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->cartService->getDecorated();
    }

    public function testAddProductToCart(): void
    {
        $directCart = new Cart('foo', 'bar');
        $lineItem = new LineItem('foo', 'bar');
        $this->productLineItemFactory->expects(static::once())
            ->method('create')
            ->willReturn($lineItem);
        $this->coreCartService->expects(static::once())
            ->method('add')
            ->willReturn($directCart);
        $cart = $this->cartService->addProductToCart('foo', 1, $directCart, $this->salesChannelContext);

        static::assertSame($cart, $directCart);
    }

    public function testRecalculateCart(): void
    {
        $directCart = new Cart('foo', 'bar');
        $this->coreCartService->expects(static::once())
            ->method('getCart')
            ->willReturn($directCart);
        $this->coreCartService->expects(static::once())
            ->method('recalculate')
            ->willReturn($directCart);

        $cart = $this->cartService->recalculateCart($this->salesChannelContext);

        static::assertSame($cart, $directCart);
    }

    public function testGetShippingCostsPrice(): void
    {
        $directCart = new Cart('foo', 'bar');
        $cartCost = 5.0;
        $calculatedPrice = new CalculatedPrice(
            $cartCost,
            $cartCost,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
        );
        $delivery = $this->createConfiguredMock(Delivery::class, [
            'getShippingCosts' => $calculatedPrice,
        ]);

        $directCart->setDeliveries(new DeliveryCollection([$delivery]));
        $expectCost = $this->cartService->getShippingCostsPrice($directCart);

        static::assertSame($expectCost, $cartCost);
    }

    public function testUpdateContextCountry(): void
    {
        $this->contextSwitchRoute->expects(static::once())
            ->method('switchContext');
        $this->contextService->expects(static::once())
            ->method('getSalesChannelContext')
            ->willReturn($this->salesChannelContext);
        $expect = $this->cartService->updateContextCountry($this->salesChannelContext, 'foo');

        static::assertInstanceOf(SalesChannelContext::class, $expect);
    }

    public function testUpdateContextShippingMethod(): void
    {
        $this->contextSwitchRoute->expects(static::once())
            ->method('switchContext');
        $this->contextService->expects(static::once())
            ->method('getSalesChannelContext')
            ->willReturn($this->salesChannelContext);
        $expect = $this->cartService->updateContextShippingMethod($this->salesChannelContext, 'foo');

        static::assertInstanceOf(SalesChannelContext::class, $expect);
    }

    public function testUpdateContextPaymentMethod(): void
    {
        $this->contextSwitchRoute->expects(static::once())
            ->method('switchContext');
        $this->contextService->expects(static::once())
            ->method('getSalesChannelContext')
            ->willReturn($this->salesChannelContext);
        $expect = $this->cartService->updateContextPaymentMethod($this->salesChannelContext, 'foo');

        static::assertInstanceOf(SalesChannelContext::class, $expect);
    }
}
