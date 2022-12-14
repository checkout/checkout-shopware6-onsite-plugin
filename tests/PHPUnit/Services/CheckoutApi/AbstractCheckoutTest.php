<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Services\CheckoutApi;

use Checkout\ApiClient;
use Checkout\CheckoutApiException;
use Checkout\CheckoutConfiguration;
use Checkout\Environment;
use Checkout\HttpClientBuilderInterface;
use Checkout\HttpMetadata;
use Checkout\Previous\CheckoutApi;
use Checkout\SdkAuthorization;
use Checkout\SdkCredentialsInterface;
use Cko\Shopware6\Factory\CheckoutApiFactory;
use Cko\Shopware6\Service\LoggerService;
use Cko\Shopware6\Tests\Traits\ContextTrait;
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
            'getPreviousClient' => $checkoutApi,
        ]);
    }

    /**
     * Fake test for the case call api should success/failed
     */
    protected function handleCheckoutRequestShouldThrowException(
        bool $apiShouldThrowException,
        string $requestMethod,
        array $result = [],
        int $code = 500
    ): void {
        $checkoutApiException = new CheckoutApiException('test');
        $checkoutApiException->http_metadata = new HttpMetadata(
            null,
            $code,
            null,
            null
        );
        $checkoutApiException->error_details = [];
        $this->apiClient
            ->method($requestMethod)
            ->willReturn($apiShouldThrowException ? static::throwException($checkoutApiException) : $result);

        if ($apiShouldThrowException) {
            static::expectException(CheckoutApiException::class);
        }

        $this->logger->expects(static::exactly($apiShouldThrowException ? 1 : 0))
            ->method('critical');
    }
}
