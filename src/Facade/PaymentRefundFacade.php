<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Facade;

use Checkout\Payments\RefundRequest;
use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Service\Builder\RefundBuilder;
use CheckoutCom\Shopware6\Service\Cart\AbstractCartService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Extractor\AbstractOrderExtractor;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderService;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderTransactionService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Service\Product\ProductService;
use CheckoutCom\Shopware6\Struct\Request\Refund\OrderRefundRequest;
use CheckoutCom\Shopware6\Struct\Request\Refund\RefundItemRequestCollection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Order\OrderConversionContext;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Throwable;

class PaymentRefundFacade
{
    private LoggerInterface $logger;

    private OrderConverter $orderConverter;

    private AbstractOrderExtractor $orderExtractor;

    private RefundBuilder $refundBuilder;

    private ProductService $productService;

    private AbstractCartService $cartService;

    private AbstractOrderService $orderService;

    private AbstractOrderTransactionService $orderTransactionService;

    private CheckoutPaymentService $checkoutPaymentService;

    private PaymentMethodService $paymentMethodService;

    private SettingsFactory $settingsFactory;

    public function __construct(
        LoggerInterface $loggerService,
        OrderConverter $orderConverter,
        AbstractOrderExtractor $orderExtractor,
        RefundBuilder $refundBuilder,
        ProductService $productService,
        AbstractCartService $cartService,
        AbstractOrderService $orderService,
        AbstractOrderTransactionService $orderTransactionService,
        CheckoutPaymentService $checkoutPaymentService,
        PaymentMethodService $paymentMethodService,
        SettingsFactory $settingsFactory
    ) {
        $this->logger = $loggerService;
        $this->orderConverter = $orderConverter;
        $this->orderExtractor = $orderExtractor;
        $this->refundBuilder = $refundBuilder;
        $this->productService = $productService;
        $this->cartService = $cartService;
        $this->orderService = $orderService;
        $this->orderTransactionService = $orderTransactionService;
        $this->checkoutPaymentService = $checkoutPaymentService;
        $this->paymentMethodService = $paymentMethodService;
        $this->settingsFactory = $settingsFactory;
    }

    public function refundPayment(OrderRefundRequest $orderRefundRequest, Context $context): void
    {
        $orderId = $orderRefundRequest->getOrderId();
        if ($orderId === null) {
            throw new CheckoutComException('The orderID of $orderRefundRequest can not be null');
        }

        $refundItemsRequest = $orderRefundRequest->getItems();
        if (!$refundItemsRequest instanceof RefundItemRequestCollection) {
            throw new CheckoutComException('The items of $orderRefundRequest can not be null');
        }

        $order = $this->getOrder($orderId, $context);
        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $orderCustomFields->getCheckoutPaymentId();
        if (empty($checkoutPaymentId)) {
            $this->logger->error(sprintf('Error while getting checkoutPaymentId from custom fields of order ID: %s', $orderId));

            throw new CheckoutPaymentIdNotFoundException($order);
        }

        $orderCurrency = $this->orderExtractor->extractCurrency($order);

        $requestLineItems = $this->refundBuilder->buildLineItems($refundItemsRequest, $order);
        if ($requestLineItems->count() === 0) {
            $message = sprintf('The line items after build are empty with order ID: %s', $orderId);
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        $orderTransaction = $this->orderExtractor->extractLatestOrderTransaction($order);
        $paymentHandler = $this->getPaymentHandler($orderTransaction);

        try {
            $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $order->getSalesChannelId());
            if (!$payment->canRefund()) {
                $message = sprintf('Checkout payment status is not captured to refund for the order ID: %s', $orderId);
                $this->logger->warning($message);

                throw new CheckoutComException($message);
            }

            // Have to get refund status (partial refund/full refund)
            $refundStatus = $this->getRefundStatus($order, $requestLineItems);

            $refundedAmount = abs(CheckoutComUtil::formatPriceCheckout(
                $requestLineItems->getPrices()->sum()->getTotalPrice(),
                $orderCurrency->getIsoCode()
            ));

            $refundRequest = new RefundRequest();
            $refundRequest->amount = $refundedAmount;

            $paymentHandler->refundPayment($checkoutPaymentId, $refundRequest, $order);

            $this->logger->info('Starting request refund', [
                'status' => $refundStatus,
                'request' => get_object_vars($refundRequest),
            ]);

            // Get plugin settings
            $settings = $this->settingsFactory->getSettings($order->getSalesChannelId());

            $this->addRefundedLineItemsToOrder($order, $requestLineItems, $context);
            $this->updateStockForProduct($requestLineItems, $context);

            $this->orderTransactionService->processTransition($orderTransaction, $refundStatus, $context);
            $this->orderService->processTransition($order, $settings, $refundStatus, $context);
        } catch (Throwable $ex) {
            $message = sprintf('Error while refund payment for order ID: %s, checkoutPaymentId: %s', $orderId, $checkoutPaymentId);
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }
    }

    private function getRefundStatus(OrderEntity $order, LineItemCollection $requestLineItems): string
    {
        $shippingCostsLineItems = $this->refundBuilder->buildLineItemsShippingCosts($order);

        if (!$this->orderService->isOnlyHaveShippingCosts($order, $requestLineItems, $shippingCostsLineItems)) {
            return CheckoutPaymentService::STATUS_PARTIALLY_REFUNDED;
        }

        // If only have shipping costs remaining after a refund, we have to add shipping costs to the request line items
        foreach ($shippingCostsLineItems as $shippingCostsLineItem) {
            $requestLineItems->add($shippingCostsLineItem);
        }

        return CheckoutPaymentService::STATUS_REFUNDED;
    }

    private function addRefundedLineItemsToOrder(OrderEntity $order, LineItemCollection $requestLineItems, Context $context): void
    {
        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext($order, $context);
        $cart = $this->orderConverter->convertToCart($order, $context);
        $cart->addLineItems($requestLineItems);

        $recalculatedCart = $this->cartService->recalculateByCart($cart, $salesChannelContext);

        $conversionContext = (new OrderConversionContext())
            ->setIncludeCustomer(false)
            ->setIncludeBillingAddress(false)
            ->setIncludeDeliveries(false)
            ->setIncludeTransactions(false)
            ->setIncludeOrderDate(false);

        $orderData = $this->orderConverter->convertToOrder($recalculatedCart, $salesChannelContext, $conversionContext);
        $orderData['id'] = $order->getId();

        $this->orderService->updateOrder($orderData, $context);
    }

    private function updateStockForProduct(LineItemCollection $requestLineItems, Context $context): void
    {
        foreach ($requestLineItems as $lineItem) {
            $lineItemPayload = $this->refundBuilder->getCheckoutLineItemPayload($lineItem);

            $productId = $lineItemPayload->getProductId();
            if (empty($productId)) {
                continue;
            }

            $this->productService->increaseStock($productId, $lineItem->getQuantity(), $context);
        }
    }

    private function getOrder(string $orderId, Context $context): OrderEntity
    {
        return $this->orderService->getOrder($context, $orderId, [
            'lineItems',
            'currency',
            'transactions.paymentMethod',
            'deliveries.shippingMethod',
            'deliveries.positions.orderLineItem',
            'deliveries.shippingOrderAddress.country',
            'deliveries.shippingOrderAddress.countryState',
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
