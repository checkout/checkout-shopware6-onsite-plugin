<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\Apm\RequestIdealSource;
use CheckoutCom\Shopware6\Handler\Method\IdealHandler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyFormatter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

class IdealHandlerTest extends AbstractPaymentHandlerTest
{
    protected PaymentHandler $paymentHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->paymentHandler = new IdealHandler(
            $this->createMock(TranslatorInterface::class),
            $this->dataValidator,
            $this->createMock(CurrencyFormatter::class),
            $this->createMock(SystemConfigService::class),
        );

        $this->setServices();
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(PaymentSourceType::$ideal, IdealHandler::getPaymentMethodType());
    }

    public function testPrepareDataForPay(): void
    {
        $dataBag = $this->getValidRequestBag();

        $this->orderExtractor->expects(static::once())->method('extractOrderNumber')->willReturn('12345');
        $this->dataValidator->expects(static::once())->method('validate');
        $order = new OrderEntity();
        $order->setId('testId');
        $order->setOrderNumber('test');
        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $order,
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
        static::assertInstanceOf(RequestIdealSource::class, $paymentRequest->source);
    }

    private function getValidRequestBag(): RequestDataBag
    {
        $sourceData = new RequestDataBag();
        $sourceData->set('bic', 'DE2131231231312');

        $dataBag = new RequestDataBag();
        $dataBag->set(RequestUtil::DATA_SOURCE, $sourceData);

        $result = new RequestDataBag();
        $result->set(RequestUtil::DATA_BAG_KEY, $dataBag);

        return $result;
    }
}
