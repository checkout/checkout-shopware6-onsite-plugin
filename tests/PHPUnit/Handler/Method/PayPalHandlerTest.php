<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Payments\PaymentRequest;
use CheckoutCom\Shopware6\Handler\Method\PayPalHandler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Handler\Source\RequestPayPalSource;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutTokenService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayPalHandlerTest extends AbstractPaymentHandlerTest
{
    protected PaymentHandler $paymentHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->paymentHandler = new PayPalHandler(
            $this->createMock(LoggerService::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(DataValidator::class),
            $this->orderExtractor,
            $this->createMock(CheckoutTokenService::class),
            $this->paymentPayFacade,
            $this->paymentFinalizeFacade
        );
    }

    public function testSnippetKey(): void
    {
        static::assertSame('checkoutCom.paymentMethod.payPalLabel', $this->paymentHandler->getSnippetKey());
    }

    public function testPrepareDataForPay(): void
    {
        $this->orderExtractor->expects(static::once())->method('extractOrderNumber')->willReturn('12345');
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
        static::assertInstanceOf(RequestPayPalSource::class, $paymentRequest->source);
    }
}
