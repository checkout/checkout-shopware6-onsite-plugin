<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\Apm\RequestEpsSource;
use CheckoutCom\Shopware6\Handler\Method\EpsHandler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\Currency\CurrencyFormatter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

class EpsHandlerTest extends AbstractPaymentHandlerTest
{
    protected PaymentHandler $paymentHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->paymentHandler = new EpsHandler(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(DataValidator::class),
            $this->createMock(CurrencyFormatter::class),
            $this->createMock(SystemConfigService::class),
        );

        $this->setServices();
    }

    public function testSnippetKey(): void
    {
        static::assertSame('checkoutCom.paymentMethod.epsLabel', $this->paymentHandler->getSnippetKey());
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(PaymentSourceType::$eps, EpsHandler::getPaymentMethodType());
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
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
        static::assertInstanceOf(RequestEpsSource::class, $paymentRequest->source);
    }
}
