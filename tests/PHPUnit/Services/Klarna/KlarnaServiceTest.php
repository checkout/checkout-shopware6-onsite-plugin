<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Services\Klarna;

use Cko\Shopware6\Service\CheckoutApi\Apm\CheckoutKlarnaService;
use Cko\Shopware6\Service\ContextService;
use Cko\Shopware6\Service\CountryService;
use Cko\Shopware6\Service\Extractor\OrderExtractor;
use Cko\Shopware6\Service\Klarna\KlarnaService;
use Cko\Shopware6\Struct\LineItemTotalPrice;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Cko\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class KlarnaServiceTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    private KlarnaService $klarnaService;

    /**
     * @var CountryService|MockObject
     */
    private $countryService;

    /**
     * @var ContextService|MockObject
     */
    private $contextService;

    /**
     * @var CheckoutKlarnaService|MockObject
     */
    private $checkoutKlarnaService;

    /**
     * @var OrderExtractor|MockObject
     */
    private $orderExtractor;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    protected function setUp(): void
    {
        $this->countryService = $this->createMock(CountryService::class);
        $this->contextService = $this->createMock(ContextService::class);
        $this->checkoutKlarnaService = $this->createMock(CheckoutKlarnaService::class);
        $this->orderExtractor = $this->createMock(OrderExtractor::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->klarnaService = new KlarnaService(
            $this->contextService,
            $this->countryService,
            $this->checkoutKlarnaService,
            $this->orderExtractor
        );
    }

    public function testCreateCreditSessionEmptyProduct(): void
    {
        $lineItemTotalPrice = new LineItemTotalPrice();
        $lineItemTotalPrice->setPrice($this->getCartPrice());

        $lineItemTotalPrice->setLineItems(new LineItemCollection());
        $lineItemTotalPrice->setDeliveries(new DeliveryCollection());
        $this->countryService->expects(static::once())
            ->method('getPurchaseCountryIsoCodeFromContext')
            ->willReturn('foo');

        $this->klarnaService->createCreditSession(
            $lineItemTotalPrice,
            $this->salesChannelContext
        );
    }

    public function testCreateCreditSessionHasProduct(): void
    {
        $lineItemTotalPrice = new LineItemTotalPrice();
        $lineItemTotalPrice->setPrice($this->getCartPrice());

        $lineItem = new LineItem('foo', 'bar');
        $lineItem->setPrice($this->getCalculatedPrice());

        $lineItemTotalPrice->setLineItems(new LineItemCollection([
            $lineItem,
        ]));
        $lineItemTotalPrice->setDeliveries(new DeliveryCollection([
            $this->getDelivery(),
        ]));
        $this->countryService->expects(static::once())
            ->method('getPurchaseCountryIsoCodeFromContext')
            ->willReturn('foo');

        $this->checkoutKlarnaService->expects(static::once())
            ->method('createCreditSession');

        $this->klarnaService->createCreditSession(
            $lineItemTotalPrice,
            $this->salesChannelContext
        );
    }

    public function testCapturePayment(): void
    {
        $order = $this->getOrder();
        $order->setPrice($this->getCartPrice());

        $currency = new CurrencyEntity();
        $currency->setId('foo');
        $currency->setIsoCode('foo');

        $this->orderExtractor->expects(static::once())
            ->method('extractCurrency')
            ->willReturn($currency);

        $this->checkoutKlarnaService->expects(static::once())
            ->method('capturePayment');

        $this->klarnaService->capturePayment(
            'foo',
            $order
        );
    }

    public function testVoidPayment(): void
    {
        $order = $this->getOrder();

        $this->orderExtractor->expects(static::once())
            ->method('extractOrderNumber');

        $this->checkoutKlarnaService->expects(static::once())
            ->method('voidPayment');

        $this->klarnaService->voidPayment(
            'foo',
            $order
        );
    }

    private function getCartPrice(): CartPrice
    {
        return new CartPrice(
            5.5,
            5.5,
            5.5,
            new CalculatedTaxCollection([
                new CalculatedTax(
                    5.5,
                    5.5,
                    5.5
                ),
            ]),
            new TaxRuleCollection(),
            'foo'
        );
    }

    private function getCalculatedPrice(): CalculatedPrice
    {
        return new CalculatedPrice(
            5.5,
            5.5,
            new CalculatedTaxCollection([
                new CalculatedTax(
                    5.5,
                    5.5,
                    5.5
                ),
            ]),
            new TaxRuleCollection()
        );
    }

    private function getDelivery(): Delivery
    {
        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId('foo');

        $country = new CountryEntity();
        $country->setId('foo');

        return new Delivery(
            new DeliveryPositionCollection(),
            new DeliveryDate(
                new \DateTime(),
                new \DateTime()
            ),
            $shippingMethod,
            new ShippingLocation($country, null, null),
            $this->getCalculatedPrice(),
        );
    }
}
