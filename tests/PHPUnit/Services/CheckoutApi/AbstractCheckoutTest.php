<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CheckoutApi;

use Checkout\ApiClient;
use Checkout\CheckoutApi;
use Checkout\CheckoutApiException;
use Checkout\CheckoutConfiguration;
use Checkout\Environment;
use Checkout\HttpClientBuilderInterface;
use Checkout\SdkAuthorization;
use Checkout\SdkCredentialsInterface;
use CheckoutCom\Shopware6\Factory\CheckoutApiFactory;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCheckoutTest extends TestCase
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

    public function setUp(): void
    {
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->logger = $this->createMock(LoggerService::class);
    }

    /**
     * @return CheckoutApiFactory|MockObject
     */
    protected function getCheckoutApiFactory()
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

    protected function handleTestCheckoutRequest(
        bool $expectThrowException,
        string $requestMethod,
        array $result = [],
        int $code = 500
    ): void {
        $checkoutApiException = new CheckoutApiException('test');
        $checkoutApiException->http_status_code = $code;

        $this->apiClient
            ->method($requestMethod)
            ->willReturn($expectThrowException ? static::throwException($checkoutApiException) : $result);

        if ($expectThrowException) {
            static::expectException(CheckoutApiException::class);
        }

        $this->logger->expects(static::exactly($expectThrowException ? 1 : 0))
            ->method('critical');
    }
}
