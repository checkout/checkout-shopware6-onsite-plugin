<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Subscriber;

use Checkout\CheckoutApiException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderService;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderTransactionService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStateMachineSubscriber implements EventSubscriberInterface
{
    private PaymentMethodService $paymentMethodService;

    private SettingsFactory $settingsFactory;

    private CheckoutPaymentService $checkoutPaymentService;

    private AbstractOrderService $orderService;

    private AbstractOrderTransactionService $orderTransactionService;

    public function __construct(
        PaymentMethodService $paymentMethodService,
        SettingsFactory $settingsFactory,
        CheckoutPaymentService $checkoutPaymentService,
        AbstractOrderService $orderService,
        AbstractOrderTransactionService $orderTransitionService
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->settingsFactory = $settingsFactory;
        $this->checkoutPaymentService = $checkoutPaymentService;
        $this->orderService = $orderService;
        $this->orderTransactionService = $orderTransitionService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order_delivery.state.shipped' => 'onOrderDeliveryEnterStateShipped',
        ];
    }

    /**
     * @throws CheckoutApiException
     * @throws Exception
     */
    public function onOrderDeliveryEnterStateShipped(OrderStateMachineStateChangeEvent $event): void
    {
        $order = $event->getOrder();
        $orderTransaction = $this->getOrderTransaction($order);
        if (!$orderTransaction instanceof OrderTransactionEntity) {
            return;
        }

        $paymentHandler = $this->paymentMethodService->getPaymentHandlerByOrderTransaction($orderTransaction);
        if (!$paymentHandler instanceof PaymentHandler || !$paymentHandler->shouldCaptureAfterShipping()) {
            return;
        }

        $checkoutOrderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $checkoutOrderCustomFields->getCheckoutPaymentId();

        // If empty payment detail we skip it
        if (empty($checkoutPaymentId)) {
            return;
        }

        // We get payment detail from checkout.com API
        $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $order->getSalesChannelId());
        if ($payment->getStatus() !== CheckoutPaymentService::STATUS_AUTHORIZED) {
            return;
        }

        // Get plugin settings
        $settings = $this->settingsFactory->getSettings($order->getSalesChannelId());

        // Capture the payment from Checkout.com
        $actionId = $paymentHandler->capturePayment($payment->getId(), $order);
        $checkoutOrderCustomFields->setLastCheckoutActionId($actionId);

        $this->orderService->updateCheckoutCustomFields($order, $checkoutOrderCustomFields, $event->getContext());

        $this->orderTransactionService->processTransition($orderTransaction, CheckoutPaymentService::STATUS_CAPTURED, $event->getContext());
        $this->orderService->processTransition($order, $settings, CheckoutPaymentService::STATUS_CAPTURED, $event->getContext());
    }

    private function getOrderTransaction(OrderEntity $order): ?OrderTransactionEntity
    {
        $orderTransactions = $order->getTransactions();
        if (!$orderTransactions instanceof OrderTransactionCollection) {
            return null;
        }

        $orderTransactions->sort(function (OrderTransactionEntity $a, OrderTransactionEntity $b): int {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });

        return $orderTransactions->last();
    }
}
