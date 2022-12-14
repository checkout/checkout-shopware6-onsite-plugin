<?php
declare(strict_types=1);

namespace Cko\Shopware6\Tests\Handler\Method;

use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\RequestIdSource;
use Checkout\Sources\Previous\SourceType;
use Cko\Shopware6\Exception\CheckoutInvalidSourceException;
use Cko\Shopware6\Handler\Method\SepaHandler;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Helper\RequestUtil;
use Cko\Shopware6\Struct\CheckoutApi\Resources\Source;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Cko\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyFormatter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class SepaHandlerTest extends AbstractPaymentHandlerTest
{
    protected PaymentHandler $paymentHandler;

    public function setUp(): void
    {
        parent::setUp();
        $this->paymentHandler = new SepaHandler(
            $this->translator,
            $this->dataValidator,
            $this->createMock(CurrencyFormatter::class),
            $this->createMock(SystemConfigService::class)
        );

        $this->setServices();
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(SourceType::$sepa, SepaHandler::getPaymentMethodType());
    }

    public function testPrepareDataForPayWithNoSourceData(): void
    {
        static::expectException(CheckoutInvalidSourceException::class);
        $dataBag = $this->getRequestBag();
        $order = new OrderEntity();
        $order->setId('testId');
        $order->setOrderNumber('test');

        $this->orderExtractor->expects(static::once())->method('extractOrderNumber')->willReturn($order->getOrderNumber());

        $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $order,
            $this->createMock(SettingStruct::class),
            $this->saleChannelContext
        );
    }

    public function testPrepareDataForPay(): void
    {
        $dataBag = $this->getValidRequestBag();
        $order = new OrderEntity();
        $order->setId('testId');
        $order->setOrderNumber('test');
        $orderAddress = new OrderAddressEntity();
        $orderAddress->setId('order_address');
        $orderAddress->setStreet('street');
        $orderAddress->setCity('city');
        $orderAddress->setZipcode('zipcode');
        $order->setBillingAddress($orderAddress);
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setId('order_customer');
        $orderCustomer->setEmail('test@test.com');
        $orderCustomer->setFirstName('first');
        $orderCustomer->setLastName('last');
        $order->setOrderCustomer($orderCustomer);

        $this->dataValidator->expects(static::once())->method('validate');
        $this->orderExtractor->expects(static::once())->method('extractOrderNumber')->willReturn($order->getOrderNumber());
        $this->orderExtractor->expects(static::once())->method('extractBillingAddress')->willReturn($order->getBillingAddress());
        $this->orderExtractor->expects(static::once())->method('extractCustomer')->willReturn($order->getOrderCustomer());
        $this->translator->expects(static::once())->method('trans')->willReturn('text');

        $source = new Source();
        $source->setId('src_tesst');
        $this->checkoutSourceService->expects(static::once())->method('createSepaSource')->willReturn($source);

        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $order,
            $this->createMock(SettingStruct::class),
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
        static::assertInstanceOf(RequestIdSource::class, $paymentRequest->source);
    }

    private function getValidRequestBag(): RequestDataBag
    {
        $sourceData = new RequestDataBag();
        $sourceData->set('firstName', 'first');
        $sourceData->set('lastName', 'last');
        $sourceData->set('iban', 'DE2131231231312');

        $dataBag = new RequestDataBag();
        $dataBag->set(RequestUtil::DATA_SOURCE, $sourceData);

        $result = new RequestDataBag();
        $result->set(RequestUtil::DATA_BAG_KEY, $dataBag);

        return $result;
    }
}
