<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Tokens\TokenType;
use CheckoutCom\Shopware6\Handler\Method\ApplePayHandler;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutTokenService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Token;
use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;
use CheckoutCom\Shopware6\Struct\DirectPay\ApplePay\AppleShippingOptionCollection;
use CheckoutCom\Shopware6\Struct\DirectPay\ApplePay\AppleShippingOptionStruct;
use CheckoutCom\Shopware6\Struct\DirectPay\ApplePay\AppleShippingPayloadStruct;
use CheckoutCom\Shopware6\Struct\DirectPay\Cart\DirectPayCartItemCollection;
use CheckoutCom\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;

class ApplePayHandlerTest extends AbstractPaymentHandlerTest
{
    /**
     * @var CheckoutTokenService|MockObject
     */
    protected $checkoutTokenService;

    public function setUp(): void
    {
        parent::setUp();
        $this->checkoutTokenService = $this->createMock(CheckoutTokenService::class);
        $this->paymentHandler = new ApplePayHandler(
            $this->translator,
            $this->dataValidator,
            $this->currencyFormatter,
            $this->systemConfigService,
        );

        $this->setServices();
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(TokenType::$applepay, ApplePayHandler::getPaymentMethodType());
    }

    public function testGetDirectShippingOptions(): void
    {
        static::assertInstanceOf(AppleShippingOptionCollection::class, $this->paymentHandler->getDirectShippingOptions());
    }

    /**
     * @dataProvider formatDirectShippingOptionProvider
     */
    public function testFormatDirectShippingOption(bool $hasDeliveryTime, string $deliveryTimeName): void
    {
        $shippingMethodEntity = new ShippingMethodEntity();
        $shippingMethodEntity->setId('foo');

        $deliveryTime = $this->createMock(DeliveryTimeEntity::class);

        if ($hasDeliveryTime) {
            $deliveryTime
                ->expects(static::exactly(empty($deliveryTimeName) ? 1 : 2))
                ->method('getName')
                ->willReturn($deliveryTimeName);

            $shippingMethodEntity->setDeliveryTime($deliveryTime);
        } else {
            $deliveryTime
                ->expects(static::never())
                ->method('getName');
        }

        $expect = $this->paymentHandler->formatDirectShippingOption(
            $shippingMethodEntity,
            5.0,
            $this->saleChannelContext
        );

        static::assertInstanceOf(AppleShippingOptionStruct::class, $expect);
    }

    /**
     * @dataProvider getDirectShippingPayloadProvider
     */
    public function testGetDirectShippingPayload(bool $hasShippingMethods, bool $cartHasShipping, bool $cartHasTax): void
    {
        $shippingMethods = null;
        $directCart = new DirectPayCartStruct(
            new DirectPayCartItemCollection(),
            new DirectPayCartItemCollection()
        );
        if ($hasShippingMethods) {
            $shippingMethods = $this->createMock(AbstractShippingOptionCollection::class);
        }

        if ($cartHasShipping) {
            $directCart->addShipping('foo', 5);
        }

        if ($cartHasTax) {
            $directCart->setTax(5);
        }

        $this->translator->expects(static::atLeastOnce())
            ->method('trans')
            ->willReturn('foo');

        $this->systemConfigService->expects(static::once())
            ->method('getString')
            ->willReturn('foo');

        $expect = $this->paymentHandler->getDirectShippingPayload(
            $shippingMethods,
            $directCart,
            $this->saleChannelContext
        );

        static::assertInstanceOf(AppleShippingPayloadStruct::class, $expect);
    }

    public function testPrepareDataForPay(): void
    {
        $requestToken = new RequestDataBag();
        $requestToken->set('paymentData', new RequestDataBag([
            'data' => 'data',
            'header' => [],
            'signature' => 'signature',
            'version' => 'version',
        ]));
        $dataBag = $this->getRequestBag($requestToken);

        $checkoutToken = (new Token())->assign(['token' => 'foo']);

        $this->checkoutTokenService->method('requestWalletToken')->willReturn($checkoutToken);

        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $this->createMock(OrderEntity::class),
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
    }

    public function formatDirectShippingOptionProvider(): array
    {
        return [
            'Test has not delivery time' => [
                false,
                '',
            ],
            'Test has delivery time but empty delivery time name' => [
                true,
                '',
            ],
            'Test has delivery time and delivery time name' => [
                true,
                '123',
            ],
        ];
    }

    public function getDirectShippingPayloadProvider(): array
    {
        return [
            'Test has not shipping methods' => [
                false,
                false,
                false,
            ],
            'Test has shipping methods but cart do not have shipping' => [
                true,
                false,
                false,
            ],
            'Test has shipping methods, cart have shipping but do not have tax' => [
                true,
                true,
                false,
            ],
            'Test has shipping methods, cart have shipping and tax' => [
                true,
                true,
                true,
            ],
        ];
    }
}
