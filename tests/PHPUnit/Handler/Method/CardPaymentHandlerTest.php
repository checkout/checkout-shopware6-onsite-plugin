<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestTokenSource;
use CheckoutCom\Shopware6\Handler\Method\CardPaymentHandler;
use CheckoutCom\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

class CardPaymentHandlerTest extends AbstractPaymentHandlerTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->paymentHandler = new CardPaymentHandler(
            $this->translator,
            $this->dataValidator,
            $this->currencyFormatter,
            $this->systemConfigService,
        );

        $this->setServices();
    }

    public function testSnippetKey(): void
    {
        static::assertSame('checkoutCom.paymentMethod.cardPaymentsLabel', $this->paymentHandler->getSnippetKey());
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(PaymentSourceType::$card, CardPaymentHandler::getPaymentMethodType());
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
