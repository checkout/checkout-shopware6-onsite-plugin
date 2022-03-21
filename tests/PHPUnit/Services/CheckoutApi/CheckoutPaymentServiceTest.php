<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CheckoutApi;

use Checkout\ApiClient;
use Checkout\CheckoutApi;
use Checkout\CheckoutApiException;
use Checkout\CheckoutConfiguration;
use Checkout\Environment;
use Checkout\HttpClientBuilderInterface;
use Checkout\Payments\PaymentRequest;
use Checkout\SdkAuthorization;
use Checkout\SdkCredentialsInterface;
use CheckoutCom\Shopware6\Factory\CheckoutApiFactory;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CheckoutPaymentServiceTest extends TestCase
{
    use ContextTrait;

    /**
     * @var MockObject|LoggerService
     */
    protected $logger;

    /**
     * @var ApiClient|MockObject
     */
    protected $apiClient;

    protected SalesChannelContext $salesChannelContext;

    protected CheckoutPaymentService $checkoutPaymentService;

    public function setUp(): void
    {
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->logger = $this->createMock(LoggerService::class);
        $this->checkoutPaymentService = new CheckoutPaymentService(
            $this->logger,
            $this->getCheckoutApiFactory()
        );
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testRequestPayment(bool $expectThrowException): void
    {
        $this->handleTestCheckoutRequest($expectThrowException, 'post');

        $paymentRequest = new PaymentRequest();
        $payment = $this->checkoutPaymentService->requestPayment(
            $paymentRequest,
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertInstanceOf(Payment::class, $payment);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testGetPaymentDetails(bool $expectThrowException): void
    {
        $this->handleTestCheckoutRequest($expectThrowException, 'get');

        $payment = $this->checkoutPaymentService->getPaymentDetails(
            'foo',
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertInstanceOf(Payment::class, $payment);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testCapturePayment(bool $expectThrowException): void
    {
        $this->handleTestCheckoutRequest($expectThrowException, 'post');

        $this->checkoutPaymentService->capturePayment(
            'foo',
            $this->salesChannelContext->getSalesChannelId()
        );
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testRefundPayment(bool $expectThrowException): void
    {
        $this->handleTestCheckoutRequest($expectThrowException, 'post');

        $this->checkoutPaymentService->refundPayment(
            'foo',
            $this->salesChannelContext->getSalesChannelId()
        );
    }

    public function requestCheckoutApiProvider(): array
    {
        return [
            'Test throw checkout api exception' => [
                true,
            ],
            'Test call api successful' => [
                false,
            ],
        ];
    }

    /**
     * @return CheckoutApiFactory|MockObject
     */
    private function getCheckoutApiFactory()
    {
        $sdkAuthorization = new SdkAuthorization('foo', 'bar');
        $checkoutApi = new CheckoutApi(
            $this->apiClient,
            new CheckoutConfiguration(
                $this->createConfiguredMock(SdkCredentialsInterface::class, [
                    'getAuthorization' => $sdkAuthorization,
                ]),
                Environment::sandbox(),
                $this->createMock(HttpClientBuilderInterface::class),
                $this->createMock(LoggerService::class),
            )
        );

        return $this->createConfiguredMock(CheckoutApiFactory::class, [
            'getClient' => $checkoutApi,
        ]);
    }

    private function handleTestCheckoutRequest(bool $expectThrowException, string $requestMethod): void
    {
        $checkoutApiException = new CheckoutApiException('test');
        $this->apiClient
            ->method($requestMethod)
            ->willReturn($expectThrowException ? static::throwException($checkoutApiException) : []);

        if ($expectThrowException) {
            static::expectException(CheckoutApiException::class);
        }

        $this->logger->expects(static::exactly($expectThrowException ? 1 : 0))
            ->method('critical');
    }
}
