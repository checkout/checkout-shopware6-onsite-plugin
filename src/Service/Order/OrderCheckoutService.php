<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use Checkout\CheckoutApiException;
use Checkout\HttpMetadata;
use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Extractor\AbstractOrderExtractor;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderCheckoutService extends AbstractOrderCheckoutService
{
    private LoggerInterface $logger;

    private AbstractOrderService $orderService;

    private AbstractOrderExtractor $orderExtractor;

    private AbstractOrderTransactionService $orderTransactionService;

    private CheckoutPaymentService $checkoutPaymentService;

    private PaymentMethodService $paymentMethodService;

    private SettingsFactory $settingsFactory;

    public function __construct(
        LoggerInterface $loggerService,
        AbstractOrderService $orderService,
        AbstractOrderExtractor $orderExtractor,
        AbstractOrderTransactionService $orderTransactionService,
        CheckoutPaymentService $checkoutPaymentService,
        PaymentMethodService $paymentMethodService,
        SettingsFactory $settingsFactory
    ) {
        $this->logger = $loggerService;
        $this->orderService = $orderService;
        $this->orderExtractor = $orderExtractor;
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
        } catch (Throwable $ex) {
            $message = sprintf('Error while getting payment details for order ID: %s, checkoutPaymentId: %s', $orderId, $checkoutPaymentId);
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        try {
            $actions = $this->checkoutPaymentService->getPaymentActions($checkoutPaymentId, $order->getSalesChannelId());

            return $payment->assign(['actions' => $actions]);
        } catch (CheckoutApiException $e) {
            $httpMetaData = $e->http_metadata;

            // If the status of API response is 404, it means actions are empty. Keep response the payment details
            if ($httpMetaData instanceof HttpMetadata && $httpMetaData->getStatusCode() === Response::HTTP_NOT_FOUND) {
                return $payment->assign(['actions' => []]);
            }

            $message = sprintf('Error while call API payment actions for order ID: %s, checkoutPaymentId: %s', $orderId, $checkoutPaymentId);
            $this->logger->error($message, ['e' => $e->getMessage()]);

            throw new CheckoutComException($message);
        } catch (Throwable $e) {
            $message = sprintf('Error while getting payment actions for order ID: %s, checkoutPaymentId: %s', $orderId, $checkoutPaymentId);
            $this->logger->error($message, ['e' => $e->getMessage()]);

            throw new CheckoutComException($message);
        }
    }

    public function capturePayment(string $orderId, Context $context): void
    {
        $order = $this->getOrder($orderId, $context);
        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $orderCustomFields->getCheckoutPaymentId();
        if (empty($checkoutPaymentId)) {
            $this->logger->error(sprintf('Error while getting checkoutPaymentId from custom fields of order ID: %s', $orderId));

            throw new CheckoutPaymentIdNotFoundException($order);
        }

        $orderTransaction = $this->orderExtractor->extractLatestOrderTransaction($order);
        $paymentHandler = $this->getPaymentHandler($orderTransaction);

        try {
            $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $order->getSalesChannelId());
            if ($payment->getStatus() !== CheckoutPaymentService::STATUS_AUTHORIZED) {
                $this->logger->warning(sprintf('Checkout payment status is not authorized to capture for the order ID: %s', $orderId));

                return;
            }

            $actionId = $paymentHandler->capturePayment($checkoutPaymentId, $order);
            $orderCustomFields->setLastCheckoutActionId($actionId);

            $this->orderService->updateCheckoutCustomFields($order, $orderCustomFields, $context);

            // Get plugin settings
            $settings = $this->settingsFactory->getSettings($order->getSalesChannelId());

            $this->orderTransactionService->processTransition($orderTransaction, CheckoutPaymentService::STATUS_CAPTURED, $context);
            $this->orderService->processTransition($order, $settings, CheckoutPaymentService::STATUS_CAPTURED, $context);
        } catch (Throwable $ex) {
            $message = sprintf('Error while capture payment for order ID: %s, checkoutPaymentId: %s', $orderId, $checkoutPaymentId);
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }
    }

    public function voidPayment(string $orderId, Context $context): void
    {
        $order = $this->getOrder($orderId, $context);
        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $orderCustomFields->getCheckoutPaymentId();
        if (empty($checkoutPaymentId)) {
            $this->logger->error(sprintf('Error while getting checkoutPaymentId from custom fields of order ID: %s', $orderId));

            throw new CheckoutPaymentIdNotFoundException($order);
        }

        $orderTransaction = $this->orderExtractor->extractLatestOrderTransaction($order);
        $paymentHandler = $this->getPaymentHandler($orderTransaction);

        try {
            $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $order->getSalesChannelId());
            if ($payment->getStatus() !== CheckoutPaymentService::STATUS_AUTHORIZED) {
                $this->logger->warning(sprintf('Checkout payment status is not authorized to void for the order ID: %s', $orderId));

                return;
            }

            $actionId = $paymentHandler->voidPayment($checkoutPaymentId, $order);
            $orderCustomFields->setLastCheckoutActionId($actionId);

            $this->orderService->updateCheckoutCustomFields($order, $orderCustomFields, $context);

            // Get plugin settings
            $settings = $this->settingsFactory->getSettings($order->getSalesChannelId());

            $this->orderTransactionService->processTransition($orderTransaction, CheckoutPaymentService::STATUS_VOID, $context);
            $this->orderService->processTransition($order, $settings, CheckoutPaymentService::STATUS_VOID, $context);
        } catch (Throwable $ex) {
            $message = sprintf('Error while void payment for order ID: %s, checkoutPaymentId: %s', $orderId, $checkoutPaymentId);
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }
    }

    private function getOrder(string $orderId, Context $context): OrderEntity
    {
        return $this->orderService->getOrder($context, $orderId, [
            'transactions.paymentMethod',
            'lineItems',
            'currency',
            'deliveries.shippingOrderAddress.country',
            'deliveries.shippingMethod',
            'billingAddress.country',
        ], function (Criteria $criteria): void {
            $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));
        });
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
