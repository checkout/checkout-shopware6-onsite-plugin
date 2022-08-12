<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\Apm\RequestP24Source;
use CheckoutCom\Shopware6\Handler\Method\Przelewy24Handler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

class Przelewy24HandlerTest extends AbstractPaymentHandlerTest
{
    protected PaymentHandler $paymentHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->paymentHandler = new Przelewy24Handler(
            $this->translator,
            $this->dataValidator,
            $this->currencyFormatter,
            $this->systemConfigService,
        );

        $this->setServices();
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(PaymentSourceType::$przelewy24, Przelewy24Handler::getPaymentMethodType());
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
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setId('testId2');
        $orderCustomer->setFirstName('Firstname');
        $orderCustomer->setLastName('Lastname');
        $orderCustomer->setEmail('test@example.com');
        $order->setOrderCustomer($orderCustomer);
        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $order,
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
        static::assertInstanceOf(RequestP24Source::class, $paymentRequest->source);
    }

    public function testPrepareDataForPayWithException(): void
    {
        static::expectException(\Exception::class);

        $dataBag = $this->getRequestBag();
        $order = new OrderEntity();
        $order->setId('testId');
        $order->setOrderNumber('test');
        $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $order,
            $this->saleChannelContext
        );
    }
}
