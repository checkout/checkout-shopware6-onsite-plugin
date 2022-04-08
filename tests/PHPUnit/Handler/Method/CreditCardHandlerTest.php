<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestTokenSource;
use CheckoutCom\Shopware6\Handler\Method\CreditCardHandler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Shopware\Core\Checkout\Order\OrderEntity;

class CreditCardHandlerTest extends AbstractPaymentHandlerTest
{
    protected PaymentHandler $paymentHandler;

    public function setUp(): void
    {
        parent::setUp();
        $this->paymentHandler = new CreditCardHandler(
            $this->createMock(LoggerService::class),
            $this->paymentPayFacade,
            $this->paymentFinalizeFacade
        );
    }

    public function testPrepareDataForPay(): void
    {
        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $this->createMock(OrderEntity::class),
            $this->setUpCustomer(),
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
        static::assertInstanceOf(RequestTokenSource::class, $paymentRequest->source);
    }
}
