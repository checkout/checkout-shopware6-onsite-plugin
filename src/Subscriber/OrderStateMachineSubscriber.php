<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Subscriber;

use Checkout\CheckoutApiException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Helper\Util;
use CheckoutCom\Shopware6\Service\CheckoutApi\Apm\CheckoutKlarnaService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderService;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderTransactionService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStateMachineSubscriber implements EventSubscriberInterface
{
    private AbstractOrderService $orderService;

    private SettingsFactory $settingsFactory;

    private AbstractOrderTransactionService $orderTransactionService;

    private CheckoutPaymentService $checkoutPaymentService;

    private CheckoutKlarnaService $checkoutKlarnaService;

    public function __construct(
        AbstractOrderService $orderService,
        SettingsFactory $settingsFactory,
        AbstractOrderTransactionService $orderTransactionService,
        CheckoutPaymentService $checkoutPaymentService,
        CheckoutKlarnaService $checkoutKlarnaService
    ) {
        $this->orderService = $orderService;
        $this->settingsFactory = $settingsFactory;
        $this->orderTransactionService = $orderTransactionService;
        $this->checkoutPaymentService = $checkoutPaymentService;
        $this->checkoutKlarnaService = $checkoutKlarnaService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_machine.order_transaction.state_changed' => 'onOrderTransactionStateChange',
            'state_enter.order_delivery.state.shipped' => 'onOrderDeliveryEnterStateShipped',
        ];
    }

    /**
     * @throws Exception
     */
    public function onOrderTransactionStateChange(StateMachineStateChangeEvent $event): void
    {
        // We only listen event at Admin side
        if (!($event->getContext()->getSource() instanceof AdminApiSource)) {
            return;
        }

        $this->handleProcessTransaction($event);
    }

    /**
     * @throws CheckoutApiException
     */
    public function onOrderDeliveryEnterStateShipped(OrderStateMachineStateChangeEvent $event): void
    {
        $order = $event->getOrder();
        $checkoutCustomField = OrderService::getCheckoutOrderCustomFields($order);
        $paymentId = $checkoutCustomField->getCheckoutPaymentId();

        if (!$paymentId) {
            return;
        }

        $this->checkoutKlarnaService->capturePayment($paymentId, $event->getSalesChannelId(), $order);
    }

    /**
     * @throws Exception
     */
    private function handleProcessTransaction(StateMachineStateChangeEvent $event): void
    {
        // We build transaction state name
        $orderTransactionRefunded = Util::buildSideEnterStateEventName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_REFUNDED,
        );
        // @TODO maybe we will handle a partial refund here

        if ($event->getStateEventName() === $orderTransactionRefunded) {
            $this->handleOrderTransactionRefunded($event);
        }
    }

    /**
     * @throws Exception
     */
    private function handleOrderTransactionRefunded(StateMachineStateChangeEvent $event): void
    {
        $orderTransaction = $this->orderTransactionService->getTransaction($event->getTransition()->getEntityId(), $event->getContext());

        /** @var OrderEntity $order */
        $order = $orderTransaction->getOrder();

        // Get plugin settings
        $settings = $this->settingsFactory->getSettings($order->getSalesChannelId());

        // We get payment detail from checkout.com API
        $payment = $this->getCheckoutPaymentDetail($order, $order->getSalesChannelId());

        // We skip if it is not a checkout.com payment
        if (!$payment instanceof Payment) {
            return;
        }

        // We refund this payment from checkout.com API
        $this->checkoutPaymentService->refundPayment($payment->getId(), $order->getSalesChannelId());

        // We update the order payment status
        $this->orderService->processTransition($order, $settings, CheckoutPaymentService::STATUS_REFUNDED, $event->getContext());
    }

    /**
     * @throws Exception
     */
    private function getCheckoutPaymentDetail(OrderEntity $order, string $salesChannelId): ?Payment
    {
        $checkoutOrderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $checkoutOrderCustomFields->getCheckoutPaymentId();

        // If empty payment detail we skip it
        if (empty($checkoutPaymentId)) {
            return null;
        }

        return $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $salesChannelId);
    }
}
