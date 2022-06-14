<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestIdSource;
use Checkout\Sources\SourceType;
use CheckoutCom\Shopware6\Exception\CheckoutInvalidSourceException;
use CheckoutCom\Shopware6\Handler\Method\SepaHandler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Source;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
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

    public function testSnippetKey(): void
    {
        static::assertSame('checkoutCom.paymentMethod.sepaLabel', $this->paymentHandler->getSnippetKey());
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
