<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Subscriber;

use Cko\Shopware6\Factory\SettingsFactory;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use Cko\Shopware6\Service\Order\OrderService;
use Cko\Shopware6\Service\Order\OrderTransactionService;
use Cko\Shopware6\Service\PaymentMethodService;
use Cko\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Cko\Shopware6\Subscriber\OrderStateMachineSubscriber;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Cko\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderStateMachineSubscriberTest extends TestCase
{
    use OrderTrait;
    use ContextTrait;

    private OrderStateMachineSubscriber $subscriber;

    /**
     * @var PaymentMethodService|MockObject
     */
    private $paymentMethodService;

    /**
     * @var SettingsFactory|MockObject
     */
    private $settingsFactory;

    /**
     * @var CheckoutPaymentService|MockObject
     */
    private $checkoutPaymentService;

    /**
     * @var OrderService|MockObject
     */
    private $orderService;

    /**
     * @var OrderTransactionService|MockObject
     */
    private $orderTransactionService;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->paymentMethodService = $this->createMock(PaymentMethodService::class);
        $this->checkoutPaymentService = $this->createMock(CheckoutPaymentService::class);
        $this->settingsFactory = $this->createMock(SettingsFactory::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->orderTransactionService = $this->createMock(OrderTransactionService::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->subscriber = new OrderStateMachineSubscriber(
            $this->paymentMethodService,
            $this->settingsFactory,
            $this->checkoutPaymentService,
            $this->orderService,
            $this->orderTransactionService,
        );
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey('state_enter.order_delivery.state.shipped', OrderStateMachineSubscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider onOrderDeliveryEnterStateShippedProvider
     */
    public function testOnOrderDeliveryEnterStateShipped(
        bool $hasOrderTransaction,
        bool $shouldCaptureAfterShipping,
        bool $hasCheckoutPaymentId,
        string $paymentStatus
    ): void {
        $paymentHandler = $this->createConfiguredMock(PaymentHandler::class, [
            'shouldCaptureAfterShipping' => $shouldCaptureAfterShipping,
        ]);

        $this->paymentMethodService->expects(static::exactly($hasOrderTransaction ? 1 : 0))
            ->method('getPaymentHandlerByOrderTransaction')
            ->willReturn($paymentHandler);

        $successCapture = $paymentStatus === CheckoutPaymentService::STATUS_AUTHORIZED && $shouldCaptureAfterShipping && $hasCheckoutPaymentId;

        if ($hasCheckoutPaymentId) {
            $payment = $this->createConfiguredMock(Payment::class, [
                'getStatus' => $paymentStatus,
            ]);

            $this->checkoutPaymentService->expects(static::exactly(1))
                ->method('getPaymentDetails')
                ->willReturn($payment);
        } else {
            $this->checkoutPaymentService->expects(static::exactly(0))
                ->method('getPaymentDetails');
        }

        $paymentHandler->expects(static::exactly($successCapture ? 1 : 0))
            ->method('capturePayment');

        $this->settingsFactory->expects(static::exactly($successCapture ? 1 : 0))
            ->method('getSettings');

        $this->orderService->expects(static::exactly($successCapture ? 1 : 0))
            ->method('processTransition');

        $this->orderTransactionService->expects(static::exactly($successCapture ? 1 : 0))
            ->method('processTransition');

        $orderTransaction = $this->getOrderTransaction();

        $order = $this->getOrder($hasCheckoutPaymentId ? 'foo' : null);
        if ($hasOrderTransaction) {
            $order->setTransactions(new OrderTransactionCollection([$orderTransaction]));
        }

        $event = new OrderStateMachineStateChangeEvent(
            'shipped',
            $order,
            $this->salesChannelContext->getContext()
        );

        $this->subscriber->onOrderDeliveryEnterStateShipped($event);
    }

    public function onOrderDeliveryEnterStateShippedProvider(): array
    {
        return [
            'Test empty order transactions' => [
                false,
                false,
                false,
                CheckoutPaymentService::STATUS_VOID,
            ],
            'Test should not capture on shipped' => [
                true,
                false,
                false,
                CheckoutPaymentService::STATUS_VOID,
            ],
            'Test empty checkout order ID' => [
                true,
                true,
                false,
                CheckoutPaymentService::STATUS_VOID,
            ],
            'Test payment status is not authorized' => [
                true,
                true,
                true,
                CheckoutPaymentService::STATUS_VOID,
            ],
            'Test capture success' => [
                true,
                true,
                true,
                CheckoutPaymentService::STATUS_AUTHORIZED,
            ],
        ];
    }
}
