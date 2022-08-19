<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\Apm\RequestSofortSource;
use CheckoutCom\Shopware6\Handler\Method\SofortHandler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

class SofortHandlerTest extends AbstractPaymentHandlerTest
{
    protected PaymentHandler $paymentHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->paymentHandler = new SofortHandler(
            $this->translator,
            $this->dataValidator,
            $this->currencyFormatter,
            $this->systemConfigService,
        );

        $this->setServices();
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(PaymentSourceType::$sofort, SofortHandler::getPaymentMethodType());
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

    public function testPrepareDataForPay(): void
    {
        $dataBag = $this->getRequestBag();
        $order = new OrderEntity();
        $order->setId('testId');
        $order->setOrderNumber('test');
        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $order,
            $this->createMock(SettingStruct::class),
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
        static::assertInstanceOf(RequestSofortSource::class, $paymentRequest->source);
    }
}
