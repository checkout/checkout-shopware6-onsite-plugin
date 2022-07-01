<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CheckoutApi;

use Checkout\Sources\SepaSourceRequest;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutSourceService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Source;

class CheckoutSourceServiceTest extends AbstractCheckoutTest
{
    protected CheckoutSourceService $checkoutSourceService;

    public function setUp(): void
    {
        parent::setUp();
        $this->checkoutSourceService = new CheckoutSourceService(
            $this->logger,
            $this->getCheckoutApiFactory()
        );
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testCreateSepaSource(bool $expectThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($expectThrowException, 'post');

        $sepaSourceRequest = new SepaSourceRequest();
        $source = $this->checkoutSourceService->createSepaSource(
            $sepaSourceRequest,
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertInstanceOf(Source::class, $source);
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
}
