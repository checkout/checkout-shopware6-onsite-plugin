<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Subscriber;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Helper\Util;
use CheckoutCom\Shopware6\Service\CheckoutApi\Apm\CheckoutKlarnaService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use CheckoutCom\Shopware6\Subscriber\OrderStateMachineSubscriber;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\ContextSource;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;

class OrderStateMachineSubscriberTest extends TestCase
{
    use OrderTrait;

    private OrderStateMachineSubscriber $subscriber;

    /**
     * @var OrderService|MockObject
     */
    private $orderService;

    /**
     * @var SettingsFactory|MockObject
     */
    private $settingsFactory;

    /**
     * @var OrderTransactionService|MockObject
     */
    private $orderTransactionService;

    /**
     * @var CheckoutPaymentService|MockObject
     */
    private $checkoutPaymentService;

    /**
     * @var CheckoutKlarnaService|MockObject
     */
    private $checkoutKlarnaService;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->orderService = $this->createMock(OrderService::class);
        $this->settingsFactory = $this->createMock(SettingsFactory::class);
        $this->orderTransactionService = $this->createMock(OrderTransactionService::class);
        $this->checkoutPaymentService = $this->createMock(CheckoutPaymentService::class);
        $this->checkoutKlarnaService = $this->createMock(CheckoutKlarnaService::class);
        $this->subscriber = new OrderStateMachineSubscriber(
            $this->orderService,
            $this->settingsFactory,
            $this->orderTransactionService,
            $this->checkoutPaymentService,
            $this->checkoutKlarnaService
        );
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey('state_machine.order_transaction.state_changed', OrderStateMachineSubscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider onOrderTransactionStateChangeProvider
     */
    public function testOnOrderTransactionStateChange(ContextSource $source, string $stateEventName, ?string $checkoutPaymentId): void
    {
        $refundedStateName = Util::buildSideEnterStateEventName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_REFUNDED
        );
        $isAdminSource = $source instanceof AdminApiSource;
        $invokedTransactionRefunded = $isAdminSource && $refundedStateName === $stateEventName;

        $hasCheckoutPaymentId = !empty($checkoutPaymentId);

        $order = $this->getOrder($checkoutPaymentId);
        $orderTransaction = $this->getOrderTransaction();
        $orderTransaction->setOrder($order);

        $payment = (new Payment())->assign(['id' => 'foo']);

        $context = $this->createConfiguredMock(Context::class, [
            'getSource' => $source,
        ]);

        $event = $this->createConfiguredMock(StateMachineStateChangeEvent::class, [
            'getContext' => $context,
        ]);

        $event->expects(static::exactly($isAdminSource ? 1 : 0))
            ->method('getStateEventName')
            ->willReturn($stateEventName);

        $this->orderTransactionService->expects(static::exactly($invokedTransactionRefunded ? 1 : 0))
            ->method('getTransaction')
            ->willReturn($orderTransaction);

        $this->checkoutPaymentService->expects(static::exactly($hasCheckoutPaymentId ? 1 : 0))
            ->method('getPaymentDetails')
            ->willReturn($payment);

        $this->checkoutPaymentService->expects(static::exactly($hasCheckoutPaymentId ? 1 : 0))
            ->method('refundPayment');

        $this->orderService->expects(static::exactly($hasCheckoutPaymentId ? 1 : 0))
            ->method('processTransition');

        $this->subscriber->onOrderTransactionStateChange($event);
    }

    public function onOrderTransactionStateChangeProvider(): array
    {
        return [
            'Test it is not admin api source' => [
                $this->createMock(SalesChannelApiSource::class),
                '',
                null,
            ],
            'Test it is not order transaction refunded' => [
                $this->createMock(AdminApiSource::class),
                Util::buildSideEnterStateEventName(
                    OrderTransactionStates::STATE_MACHINE,
                    OrderTransactionStates::STATE_OPEN
                ),
                null,
            ],
            'Test get transaction but order transaction does not found' => [
                $this->createMock(AdminApiSource::class),
                Util::buildSideEnterStateEventName(
                    OrderTransactionStates::STATE_MACHINE,
                    OrderTransactionStates::STATE_REFUNDED
                ),
                null,
            ],
            'Test order does not have checkout payment id' => [
                $this->createMock(AdminApiSource::class),
                Util::buildSideEnterStateEventName(
                    OrderTransactionStates::STATE_MACHINE,
                    OrderTransactionStates::STATE_REFUNDED
                ),
                null,
            ],
            'Test process refunded success' => [
                $this->createMock(AdminApiSource::class),
                Util::buildSideEnterStateEventName(
                    OrderTransactionStates::STATE_MACHINE,
                    OrderTransactionStates::STATE_REFUNDED
                ),
                'foo',
            ],
        ];
    }
}
