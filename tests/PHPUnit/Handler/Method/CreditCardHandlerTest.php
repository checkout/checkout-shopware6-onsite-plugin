<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestTokenSource;
use CheckoutCom\Shopware6\Handler\Method\CreditCardHandler;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreditCardHandlerTest extends AbstractPaymentHandlerTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->paymentHandler = new CreditCardHandler(
            $this->createMock(LoggerService::class),
            $this->createMock(TranslatorInterface::class),
            $this->paymentPayFacade,
            $this->paymentFinalizeFacade
        );
    }

    /**
     * @dataProvider prepareDataForPayProvider
     */
    public function testPrepareDataForPay(?string $token): void
    {
        $dataBag = $this->getRequestBag($token);

        if ($token === null) {
            static::expectException(Exception::class);
        }
        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $this->createMock(OrderEntity::class),
            $this->setUpCustomer(),
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
            'Test token is string expect success' => [
                'any token string',
            ],
        ];
    }
}
