<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Payments\PaymentRequest;
use CheckoutCom\Shopware6\Handler\Method\ApplePayHandler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApplePayHandlerTest extends AbstractPaymentHandlerTest
{
    protected PaymentHandler $paymentHandler;

    public function setUp(): void
    {
        parent::setUp();
        $this->paymentHandler = new ApplePayHandler(
            $this->createMock(LoggerService::class),
            $this->createMock(TranslatorInterface::class),
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
    }
}
