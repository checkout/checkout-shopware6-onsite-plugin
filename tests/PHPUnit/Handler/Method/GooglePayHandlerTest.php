<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Tokens\TokenType;
use CheckoutCom\Shopware6\Handler\Method\GooglePayHandler;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutTokenService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Token;
use CheckoutCom\Shopware6\Tests\Handler\AbstractPaymentHandlerTest;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

class GooglePayHandlerTest extends AbstractPaymentHandlerTest
{
    /**
     * @var CheckoutTokenService|MockObject
     */
    protected $checkoutTokenService;

    public function setUp(): void
    {
        parent::setUp();
        $this->checkoutTokenService = $this->createMock(CheckoutTokenService::class);
        $this->paymentHandler = new GooglePayHandler(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(DataValidator::class),
            $this->createMock(SystemConfigService::class),
        );

        $this->setServices();
    }

    public function testSnippetKey(): void
    {
        static::assertSame('checkoutCom.paymentMethod.googlePayLabel', $this->paymentHandler->getSnippetKey());
    }

    public function testPaymentMethodType(): void
    {
        static::assertSame(TokenType::$googlepay, GooglePayHandler::getPaymentMethodType());
    }

    public function testPrepareDataForPay(): void
    {
        $requestToken = new RequestDataBag();
        $requestToken->set('protocolVersion', 'protocolVersion');
        $requestToken->set('signedMessage', 'signedMessage');
        $requestToken->set('signature', 'signature');
        $dataBag = $this->getRequestBag($requestToken);

        $checkoutToken = (new Token())->assign(['token' => 'foo']);

        $this->checkoutTokenService->method('requestWalletToken')->willReturn($checkoutToken);

        $paymentRequest = $this->paymentHandler->prepareDataForPay(
            $this->createMock(PaymentRequest::class),
            $dataBag,
            $this->createMock(OrderEntity::class),
            $this->saleChannelContext
        );

        static::assertInstanceOf(PaymentRequest::class, $paymentRequest);
    }
}
