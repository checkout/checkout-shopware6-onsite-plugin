<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Throwable;

class OrderCheckoutService extends AbstractOrderCheckoutService
{
    private LoggerInterface $logger;

    private AbstractOrderService $orderService;

    private AbstractOrderTransactionService $orderTransactionService;

    private CheckoutPaymentService $checkoutPaymentService;

    private PaymentMethodService $paymentMethodService;

    private SettingsFactory $settingsFactory;

    public function __construct(
        LoggerInterface $loggerService,
        AbstractOrderService $orderService,
        AbstractOrderTransactionService $orderTransactionService,
        CheckoutPaymentService $checkoutPaymentService,
        PaymentMethodService $paymentMethodService,
        SettingsFactory $settingsFactory
    ) {
        $this->logger = $loggerService;
        $this->orderService = $orderService;
        $this->orderTransactionService = $orderTransactionService;
        $this->checkoutPaymentService = $checkoutPaymentService;
        $this->paymentMethodService = $paymentMethodService;
        $this->settingsFactory = $settingsFactory;
    }

    public function getDecorated(): AbstractOrderCheckoutService
    {
        throw new DecorationPatternException(self::class);
    }

    public function getCheckoutPayment(string $orderId, Context $context): Payment
    {
        $order = $this->orderService->getOrder($context, $orderId);

        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $orderCustomFields->getCheckoutPaymentId();
        if (empty($checkoutPaymentId)) {
            $this->logger->error(sprintf('Error while getting checkoutPaymentId from custom fields of order ID: %s', $orderId));

            throw new CheckoutPaymentIdNotFoundException($order);
        }

        try {
            $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $order->getSalesChannelId());
            $actions = $this->checkoutPaymentService->getPaymentActions($checkoutPaymentId, $order->getSalesChannelId());

            return $payment->assign(['actions' => $actions]);
        } catch (Throwable $ex) {
            $message = sprintf('Error while getting payment details for order ID: %s, checkoutPaymentId: %s', $orderId, $checkoutPaymentId);
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }
    }

    public function capturePayment(string $orderId, Context $context): void
    {
        $order = $this->orderService->getOrder($context, $orderId, [
            'transactions.paymentMethod',
            'lineItems',
            'currency',
            'deliveries.shippingOrderAddress.country',
            'deliveries.shippingMethod',
            'billingAddress.country',
        ], function (Criteria $criteria): void {
            $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));
        });

        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $orderCustomFields->getCheckoutPaymentId();
        if (empty($checkoutPaymentId)) {
            $this->logger->error(sprintf('Error while getting checkoutPaymentId from custom fields of order ID: %s', $orderId));

            throw new CheckoutPaymentIdNotFoundException($order);
        }

        $orderTransaction = $this->getOrderTransaction($order);
        $paymentHandler = $this->getPaymentHandler($orderTransaction);

        try {
            $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $order->getSalesChannelId());

            if ($payment->getStatus() !== CheckoutPaymentService::STATUS_AUTHORIZED) {
                return;
            }

            $paymentHandler->capturePayment($checkoutPaymentId, $order);

            // Get plugin settings
            $settings = $this->settingsFactory->getSettings($order->getSalesChannelId());

            $this->orderTransactionService->processTransition($orderTransaction, CheckoutPaymentService::STATUS_CAPTURED, $context);
            $this->orderService->processTransition($order, $settings, CheckoutPaymentService::STATUS_CAPTURED, $context);
        } catch (Throwable $ex) {
            $message = sprintf('Error while getting payment details for order ID: %s, checkoutPaymentId: %s', $orderId, $checkoutPaymentId);
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }
    }

    private function getOrderTransaction(OrderEntity $order): OrderTransactionEntity
    {
        $orderTransactions = $order->getTransactions();
        if (!$orderTransactions instanceof OrderTransactionCollection) {
            $this->logger->critical(sprintf('orderTransactions must be instance of OrderTransactionCollection with Order ID: %s', $order->getId()));

            throw new InvalidOrderException($order->getId());
        }

        $orderTransaction = $orderTransactions->last();
        if (!$orderTransaction instanceof OrderTransactionEntity) {
            $this->logger->critical(sprintf('Could not find an order transaction with Order ID: %s', $order->getId()));

            throw new InvalidOrderException($order->getId());
        }

        return $orderTransaction;
    }

    private function getPaymentHandler(OrderTransactionEntity $orderTransaction): PaymentHandler
    {
        $paymentHandler = $this->paymentMethodService->getPaymentHandlerByOrderTransaction($orderTransaction);
        if (!$paymentHandler instanceof PaymentHandler) {
            $message = sprintf('Could not find Payment Handler with Order Transaction ID: %s', $orderTransaction->getId());
            $this->logger->critical($message);

            throw new CheckoutComException($message);
        }

        return $paymentHandler;
    }
}
