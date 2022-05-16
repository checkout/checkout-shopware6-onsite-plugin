<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestTokenSource;
use CheckoutCom\Shopware6\Handler\Method\CreditCardHandler;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreditCardHandlerTest extends AbstractPaymentHandlerTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->paymentHandler = new CreditCardHandler(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(DataValidator::class),
            $this->createMock(SystemConfigService::class),
        );

        $this->setServices();
    }

    public function testSnippetKey(): void
    {
        static::assertSame('checkoutCom.paymentMethod.creditCardLabel', $this->paymentHandler->getSnippetKey());
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(PaymentSourceType::$card, CreditCardHandler::getPaymentMethodType());
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
