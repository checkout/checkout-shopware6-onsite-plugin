<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\RequestTokenSource;
use Cko\Shopware6\Handler\Method\CardPaymentHandler;
use Cko\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Cko\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

class CardPaymentHandlerTest extends AbstractPaymentHandlerTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->paymentHandler = new CardPaymentHandler(
            $this->translator,
            $this->dataValidator,
            $this->currencyFormatter,
            $this->systemConfigService,
        );

        $this->setServices();
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(PaymentSourceType::$card, CardPaymentHandler::getPaymentMethodType());
    }

    public function testGetDirectShippingOptions(): void
    {
        static::expectException(Exception::class);
        $this->paymentHandler->getDirectShippingOptions();
    }

    public function testFormatDirectShippingOption(): void
    {
        static::expectException(Exception::class);
        $this->paymentHandler->formatDirectShippingOption(
            $this->createMock(ShippingMethodEntity::class),
            5.0,
            $this->saleChannelContext
        );
    }

    public function testGetDirectShippingPayload(): void
    {
        static::expectException(Exception::class);
        $this->paymentHandler->getDirectShippingPayload(
            null,
            $this->createMock(DirectPayCartStruct::class),
            $this->saleChannelContext
        );
    }

    public function testPrepareDataForPayOfShouldSaveSource(): void
    {
        $dataBag = $this->getRequestBag('foo', true);

        $this->settingsFactory->expects(static::never())->method('get3dSecureConfig');

        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $this->createMock(OrderEntity::class),
            $this->createMock(SettingStruct::class),
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
        static::assertInstanceOf(RequestTokenSource::class, $paymentRequest->source);
    }

    public function testPrepareDataForPay(): void
    {
        $dataBag = $this->getRequestBag('foo', false);

        $this->settingsFactory->expects(static::once())->method('get3dSecureConfig')->willReturn(true);

        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $this->createMock(OrderEntity::class),
            $this->createMock(SettingStruct::class),
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
        static::assertInstanceOf(RequestTokenSource::class, $paymentRequest->source);
    }

    public function prepareDataForPayProvider(): array
    {
        return [
            'Test token is not string in request data bag' => [
                null,
            ],
            'Test token is string expect success, 3ds false' => [
                'any token string',
                false,
            ],
            'Test token is string expect success, 3ds true' => [
                'any token string',
                true,
            ],
        ];
    }
}
