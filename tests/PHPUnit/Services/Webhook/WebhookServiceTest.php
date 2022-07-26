<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Webhook;

use CheckoutCom\Shopware6\Facade\PaymentRefundFacade;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use CheckoutCom\Shopware6\Service\Webhook\WebhookService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Webhook;
use CheckoutCom\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use CheckoutCom\Shopware6\Struct\WebhookReceiveDataStruct;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
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
    use ContextTrait;

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

    /**
     * @var PaymentRefundFacade|MockObject
     */
    private $paymentRefundFacade;

    /**
     * @var MockObject|Context
     */
    private $context;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->orderTransactionService = $this->createMock(OrderTransactionService::class);
        $this->settingsFactory = $this->createMock(SettingsFactory::class);
        $this->paymentRefundFacade = $this->createMock(PaymentRefundFacade::class);
        $this->context = $this->getContext($this);

        $this->webhookService = new WebhookService(
            $this->logger,
            $this->orderService,
            $this->orderTransactionService,
            $this->settingsFactory,
            $this->paymentRefundFacade,
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

    public function testHandleOfSameActionId(): void
    {
        $checkoutOrderCustomFields = new OrderCustomFieldsStruct();
        $checkoutOrderCustomFields->setLastCheckoutActionId('action_id');

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getCustomFields' => [
                OrderService::CHECKOUT_CUSTOM_FIELDS => $checkoutOrderCustomFields->jsonSerialize(),
            ],
        ]);

        $data = new WebhookReceiveDataStruct();
        $data->setActionId('action_id');
        $data->setReference('foo');

        $this->orderService->expects(static::once())
            ->method('getOrderByOrderNumber')
            ->willReturn($order);

        $order->expects(static::never())
            ->method('getTransactions');

        $this->logger->expects(static::once())
            ->method('warning');

        $this->webhookService->handle($data, $this->context);
    }

    public function testHandleOfInvalidInstanceOfOrderTransaction(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getCustomFields' => [],
            'getTransactions' => null,
        ]);

        $data = new WebhookReceiveDataStruct();
        $data->setActionId('action_id');
        $data->setReference('foo');

        $this->orderService->expects(static::once())
            ->method('getOrderByOrderNumber')
            ->willReturn($order);

        static::expectException(InvalidOrderException::class);

        $this->webhookService->handle($data, $this->context);
    }

    public function testHandleOfInvalidInstanceOfOrderTransactionEntity(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getCustomFields' => [],
            'getTransactions' => new OrderTransactionCollection(),
        ]);

        $data = new WebhookReceiveDataStruct();
        $data->setActionId('action_id');
        $data->setReference('foo');

        $this->orderService->expects(static::once())
            ->method('getOrderByOrderNumber')
            ->willReturn($order);

        static::expectException(InvalidOrderException::class);

        $this->webhookService->handle($data, $this->context);
    }

    public function testHandleOfInvalidWebhookStatus(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getCustomFields' => [],
            'getTransactions' => new OrderTransactionCollection([
                $this->createConfiguredMock(OrderTransactionEntity::class, [
                    'getId' => 'foo',
                ]),
            ]),
        ]);

        $data = new WebhookReceiveDataStruct();
        $data->setActionId('action_id');
        $data->setReference('foo');
        $data->setType('invalid_status');

        $this->orderService->expects(static::once())
            ->method('getOrderByOrderNumber')
            ->willReturn($order);

        static::expectException(Exception::class);

        $this->webhookService->handle($data, $this->context);
    }

    public function testHandleOfRefundedStatus(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getCustomFields' => [],
            'getTransactions' => new OrderTransactionCollection([
                $this->createConfiguredMock(OrderTransactionEntity::class, [
                    'getId' => 'foo',
                ]),
            ]),
        ]);

        $data = new WebhookReceiveDataStruct();
        $data->setActionId('action_id');
        $data->setReference('foo');
        $data->setType(CheckoutWebhookService::PAYMENT_REFUNDED);

        $settings = $this->createMock(SettingStruct::class);

        $this->orderService->expects(static::once())
            ->method('getOrderByOrderNumber')
            ->willReturn($order);

        $this->paymentRefundFacade->expects(static::once())
            ->method('refundPaymentByWebhook')
            ->with(
                static::isInstanceOf(OrderEntity::class),
                $data,
                static::isInstanceOf(Context::class)
            );

        $this->settingsFactory->expects(static::once())
            ->method('getSettings')
            ->willReturn($settings);

        $this->orderTransactionService->expects(static::never())
            ->method('processTransition')
            ->with(
                static::isInstanceOf(OrderTransactionEntity::class),
                CheckoutPaymentService::STATUS_REFUNDED,
                static::isInstanceOf(Context::class)
            );

        $this->orderService->expects(static::never())
            ->method('processTransition')
            ->with(
                static::isInstanceOf(OrderEntity::class),
                $settings,
                CheckoutPaymentService::STATUS_REFUNDED,
                static::isInstanceOf(Context::class)
            );

        $this->webhookService->handle($data, $this->context);
    }

    /**
     * @dataProvider getHandleStatusProvider
     */
    public function testHandleOfOtherStatus(string $type): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getCustomFields' => [],
            'getTransactions' => new OrderTransactionCollection([
                $this->createConfiguredMock(OrderTransactionEntity::class, [
                    'getId' => 'foo',
                ]),
            ]),
        ]);

        $data = new WebhookReceiveDataStruct();
        $data->setActionId('action_id');
        $data->setReference('foo');
        $data->setType($type);

        $this->orderService->expects(static::once())
            ->method('getOrderByOrderNumber')
            ->willReturn($order);

        $this->paymentRefundFacade->expects(static::never())
            ->method('refundPaymentByWebhook');

        $this->orderTransactionService->expects(static::once())
            ->method('processTransition');

        $this->orderService->expects(static::once())
            ->method('processTransition');

        $this->webhookService->handle($data, $this->context);
    }

    public function getHandleStatusProvider(): array
    {
        return [
            'capture' => [
                'type' => CheckoutWebhookService::PAYMENT_CAPTURED,
            ],
            'voided' => [
                'type' => CheckoutWebhookService::PAYMENT_VOIDED,
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
        ];
    }
}
