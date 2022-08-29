<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CheckoutApi;

use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Webhook;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Storefront\Framework\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class CheckoutWebhookServiceTest extends AbstractCheckoutTest
{
    protected CheckoutWebhookService $checkoutWebhookService;

    /**
     * @var RouterInterface|MockObject
     */
    private $router;

    public function setUp(): void
    {
        parent::setUp();

        $this->router = $this->createMock(Router::class);
        $this->checkoutWebhookService = new CheckoutWebhookService(
            $this->logger,
            $this->getCheckoutApiFactory(),
            $this->router
        );
    }

    /**
     * To make sure the list of handling event types updated correctly if there is any change
     */
    public function testHandlingWebhookEvents(): void
    {
        static::assertSame(CheckoutWebhookService::WEBHOOK_EVENTS, [
            CheckoutWebhookService::PAYMENT_CAPTURED,
            CheckoutWebhookService::PAYMENT_VOIDED,
            CheckoutWebhookService::PAYMENT_REFUNDED,
            CheckoutWebhookService::PAYMENT_PENDING,
            CheckoutWebhookService::PAYMENT_DECLINED,
            CheckoutWebhookService::PAYMENT_EXPIRED,
            CheckoutWebhookService::PAYMENT_CANCELED,
        ]);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testRegisterWebhook(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException(
            $apiShouldThrowException,
            'post',
            [
                'id' => 'test',
                'headers' => ['authorization' => 'xxxxxx'],
            ]
        );

        $this->router->expects(static::once())->method('generate')->willReturn('http://test.dev');
        $webhook = $this->checkoutWebhookService->registerWebhook();

        static::assertInstanceOf(Webhook::class, $webhook);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testRetrieveWebhook(bool $apiShouldThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException(
            $apiShouldThrowException,
            'get'
        );

        $this->checkoutWebhookService->retrieveWebhook('test', 'foo');
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
