<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Webhook;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use CheckoutCom\Shopware6\Service\Webhook\WebhookService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Webhook;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use CheckoutCom\Shopware6\Struct\WebhookReceiveDataStruct;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpKernel\Log\Logger;

class WebhookServiceTest extends TestCase
{
    private WebhookService $webhookService;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var OrderTransactionService|MockObject
     */
    private $orderTransactionService;

    /**
     * @var OrderService|MockObject
     */
    private $orderService;

    /**
     * @var SettingsFactory|MockObject
     */
    private $settingsFactory;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->orderTransactionService = $this->createMock(OrderTransactionService::class);
        $this->settingsFactory = $this->createMock(SettingsFactory::class);

        $this->webhookService = new WebhookService(
            $this->logger,
            $this->orderService,
            $this->orderTransactionService,
            $this->settingsFactory,
        );
    }

    /**
     * @dataProvider getAuthenticateData
     */
    public function testAuthenticateToken(string $token, string $tokenFromSetting, $expected): void
    {
        $webhook = new Webhook();
        $webhook->setAuthorization($tokenFromSetting);
        $this->settingsFactory->expects(static::once())->method('getWebhookConfig')->willReturn($webhook);
        $result = $this->webhookService->authenticateToken($token);

        static::assertSame($result, $expected);
    }

    public function getAuthenticateData(): array
    {
        return [
            'false' => ['test', 'test2', false],
            'true' => ['test', 'test', true],
        ];
    }

    /**
     * @dataProvider getWebhookRequestDataWithInvalidOrder
     */
    public function testHandleWithInvalidOrder(?OrderTransactionCollection $transactionCollection): void
    {
        static::expectException(InvalidOrderException::class);

        $order = new OrderEntity();
        $order->setId('test');
        if ($transactionCollection) {
            $order->setTransactions($transactionCollection);
        }

        $this->orderService->expects(static::once())->method('getOrderByOrderNumber')->willReturn($order);

        $data = new WebhookReceiveDataStruct();
        $data->setId('test');
        $data->setCreatedOn('2019-06-07T08:36:43Z');
        $data->setReference('10000');
        $data->setType('test');

        $this->webhookService->handle($data, Context::createDefaultContext());
    }

    public function getWebhookRequestDataWithInvalidOrder(): array
    {
        return [
            'with null transaction collection' => [null],
            'with null transaction' => [new OrderTransactionCollection([])],
        ];
    }

    /**
     * @dataProvider getWebhookRequestData
     */
    public function testHandle(string $type): void
    {
        $data = new WebhookReceiveDataStruct();
        $data->setId('test');
        $data->setCreatedOn('2019-06-07T08:36:43Z');
        $data->setReference('10000');
        $data->setType($type);

        $order = new OrderEntity();
        $order->setId('test');
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('transaction');

        $order->setTransactions(new OrderTransactionCollection([$orderTransaction]));

        if ($data->getType() === 'unknown_status') {
            $this->logger->expects(static::once())->method('critical');

            static::expectException(Exception::class);
        } else {
            $this->orderService->expects(static::once())->method('processTransition');
            $this->orderTransactionService->expects(static::once())->method('processTransition');
        }

        $this->orderService->expects(static::once())->method('getOrderByOrderNumber')->willReturn($order);
        $this->settingsFactory->expects(static::once())->method('getSettings')->willReturn(new SettingStruct());

        $this->webhookService->handle($data, Context::createDefaultContext());
    }

    public function getWebhookRequestData(): array
    {
        return [
            'capture' => [
                'type' => CheckoutWebhookService::PAYMENT_CAPTURED,
            ],
            'voided' => [
                'type' => CheckoutWebhookService::PAYMENT_VOIDED,
            ],
            'refunded' => [
                'type' => CheckoutWebhookService::PAYMENT_REFUNDED,
            ],
            'pending' => [
                'type' => CheckoutWebhookService::PAYMENT_PENDING,
            ],
            'declined' => [
                'type' => CheckoutWebhookService::PAYMENT_DECLINED,
            ],
            'expired' => [
                'type' => CheckoutWebhookService::PAYMENT_EXPIRED,
            ],
            'canceled' => [
                'type' => CheckoutWebhookService::PAYMENT_CANCELED,
            ],
            'unknown status' => [
                'type' => 'unknown_status',
            ],
        ];
    }
}
