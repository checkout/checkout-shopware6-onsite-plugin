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
use CheckoutCom\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use CheckoutCom\Shopware6\Struct\LineItem\ProductPromotionCollection;
use CheckoutCom\Shopware6\Struct\Request\Refund\OrderRefundRequest;
use CheckoutCom\Shopware6\Struct\Request\Refund\RefundItemRequestCollection;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use CheckoutCom\Shopware6\Struct\WebhookReceiveDataStruct;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Order\OrderConversionContext;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Util\FloatComparator;
use Shopware\Core\System\Currency\CurrencyEntity;
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
        $orderCurrency = $this->orderExtractor->extractCurrency($order);
        $productPromotions = $this->refundBuilder->buildProductPromotions($order);

        $requestLineItems = $this->refundBuilder->buildLineItems(
            $refundItemsRequest,
            $order,
            $orderCurrency,
            $productPromotions
        );

        if ($requestLineItems->count() === 0) {
            $message = sprintf('The line items after build are empty with order number: %s', $order->getOrderNumber());
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        // Get plugin settings
        $settings = $this->settingsFactory->getSettings($order->getSalesChannelId());
        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);

        // Have to get refund status (partial refund/full refund)
        $refundStatus = $this->getRefundStatus($order, $orderCustomFields, $requestLineItems, $settings);

        $this->handleRefundPayment(
            $order,
            $orderCustomFields,
            $orderCurrency,
            $requestLineItems,
            $settings,
            $productPromotions,
            $refundStatus,
            $context
        );
    }

    public function refundPaymentByWebhook(OrderEntity $order, WebhookReceiveDataStruct $receiveDataStruct, Context $context): void
    {
        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $orderCustomFields->getCheckoutPaymentId();
        if (empty($checkoutPaymentId)) {
            $this->logger->error(sprintf('Error while getting checkoutPaymentId from custom fields of order number: %s', $order->getOrderNumber()));

            throw new CheckoutPaymentIdNotFoundException($order);
        }

        $orderTransaction = $this->orderExtractor->extractLatestOrderTransaction($order);
        $requestLineItems = $this->refundBuilder->buildLineItemsForWebhook($receiveDataStruct);

        try {
            // Have to get refund status (partial refund/full refund)
            $refundStatus = $this->getWebhookRefundStatus($order, $requestLineItems);

            $orderCustomFields->setLastCheckoutActionId($receiveDataStruct->getActionId());
            $orderCustomFields->setIsRefundedFromHub(true);

            // Get plugin settings
            $settings = $this->settingsFactory->getSettings($order->getSalesChannelId());

            $this->addRefundedLineItemsToOrder($order, $requestLineItems, null, $context);

            $this->orderService->updateCheckoutCustomFields($order, $orderCustomFields, $context);
            $this->orderTransactionService->processTransition($orderTransaction, $refundStatus, $context);
            $this->orderService->processTransition($order, $settings, $refundStatus, $context);
        } catch (Throwable $ex) {
            $message = sprintf('Error while refund payment by webhook for order number: %s, checkoutPaymentId: %s', $order->getOrderNumber(), $checkoutPaymentId);
            $this->logger->error($message, [
                'error' => $ex->getMessage(),
            ]);

            throw new CheckoutComException($message);
        }
    }

    public function fixPriceDifference(string $orderId, Context $context): void
    {
        $order = $this->getOrder($orderId, $context);
        $orderCurrency = $this->orderExtractor->extractCurrency($order);

        $deliveryTotalPrice = 0.0;
        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        if (!$orderCustomFields->isShippingCostRefunded()) {
            $shippingCostsLineItems = $this->refundBuilder->buildLineItemsShippingCosts($order);
            $deliveryTotalPrice = abs($shippingCostsLineItems->getPrices()->sum()->getTotalPrice());
        }

        $price = FloatComparator::cast(
            $order->getPrice()->getTotalPrice() - $deliveryTotalPrice
        );

        if (FloatComparator::lessThanOrEquals($price, 0.0)) {
            $message = 'The order total price can not less than or equals 0';
            $this->logger->warning($message);

            throw new CheckoutComException($message);
        }

        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $requestLineItems = $this->refundBuilder->buildLineItemsForFixPriceDifference($price);

        $this->handleRefundPayment(
            $order,
            $orderCustomFields,
            $orderCurrency,
            $requestLineItems,
            $this->settingsFactory->getSettings($order->getSalesChannelId()),
            null,
            CheckoutPaymentService::STATUS_REFUNDED,
            $context,
        );
    }

    private function getWebhookRefundStatus(OrderEntity $order, LineItemCollection $requestLineItems): string
    {
        $absOrderTotal = abs($order->getPrice()->getTotalPrice());
        $absRequestTotal = abs($requestLineItems->getPrices()->sum()->getTotalPrice());
        if (FloatComparator::lessThan($absOrderTotal, $absRequestTotal)) {
            $message = sprintf('the remaining order total amount can not less than the refund request amount for order number: %s', $order->getOrderNumber());
            $this->logger->error($message, [
                'orderTotal' => $absOrderTotal,
                'requestTotal' => $absRequestTotal,
            ]);

            throw new CheckoutComException($message);
        }

        return FloatComparator::equals(
            $absOrderTotal,
            $absRequestTotal
        ) ? CheckoutPaymentService::STATUS_REFUNDED : CheckoutPaymentService::STATUS_PARTIALLY_REFUNDED;
    }

    private function getRefundStatus(
        OrderEntity $order,
        OrderCustomFieldsStruct $orderCustomFields,
        LineItemCollection $requestLineItems,
        SettingStruct $settingStruct
    ): string {
        if (!$this->isRefundAllProducts($order, $requestLineItems)) {
            return CheckoutPaymentService::STATUS_PARTIALLY_REFUNDED;
        }

        $shippingCostsLineItems = $this->refundBuilder->buildLineItemsShippingCosts($order);
        if ($this->orderService->isOnlyHaveShippingCosts($order, $requestLineItems, $shippingCostsLineItems)) {
            $status = CheckoutPaymentService::STATUS_REFUNDED;
        } else {
            $status = CheckoutPaymentService::STATUS_PARTIALLY_REFUNDED;
        }

        if ($settingStruct->isIncludeShippingCostsRefund()) {
            // If only have shipping costs remaining after a refund, we have to add shipping costs to the request line items
            foreach ($shippingCostsLineItems as $shippingCostsLineItem) {
                $requestLineItems->add($shippingCostsLineItem);
            }

            $orderCustomFields->setIsShippingCostRefunded(true);
        }

        return $status;
    }

    private function isRefundAllProducts(OrderEntity $order, LineItemCollection $requestLineItems): bool
    {
        $totalRefunded = 0;
        $totalProduct = 0;

        foreach ($order->getLineItems() as $lineItem) {
            $lineItemPayload = $this->refundBuilder->getCheckoutLineItemPayload($lineItem);
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $totalProduct += $lineItem->getQuantity();
            }

            if (empty($lineItemPayload->getRefundLineItemId())) {
                continue;
            }

            // If the line item has line-item ID, it means the line-item is refunded line item
            $totalRefunded += $lineItem->getQuantity();
        }

        return ($requestLineItems->getTotalQuantity() + $totalRefunded) === $totalProduct;
    }

    private function handleRefundPayment(
        OrderEntity $order,
        OrderCustomFieldsStruct $orderCustomFields,
        CurrencyEntity $orderCurrency,
        LineItemCollection $requestLineItems,
        SettingStruct $settings,
        ?ProductPromotionCollection $productPromotions,
        string $refundStatus,
        Context $context
    ): void {
        if ($orderCustomFields->isRefundedFromHub()) {
            $message = sprintf('This order has already been refunded from checkout.com hub: %s', $order->getId());
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        $checkoutPaymentId = $orderCustomFields->getCheckoutPaymentId();
        if (empty($checkoutPaymentId)) {
            $this->logger->error(sprintf('Error while getting checkoutPaymentId from custom fields of order number: %s', $order->getOrderNumber()));

            throw new CheckoutPaymentIdNotFoundException($order);
        }

        $orderTransaction = $this->orderExtractor->extractLatestOrderTransaction($order);
        $paymentHandler = $this->getPaymentHandler($orderTransaction);

        try {
            $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $order->getSalesChannelId());
            if (!$payment->canRefund()) {
                $message = sprintf('Checkout payment status is not captured to refund for the order number: %s', $order->getOrderNumber());
                $this->logger->warning($message);

                throw new CheckoutComException($message);
            }

            $refundedAmount = abs(CheckoutComUtil::formatPriceCheckout(
                $requestLineItems->getPrices()->sum()->getTotalPrice(),
                $orderCurrency->getIsoCode()
            ));

            $refundRequest = new RefundRequest();
            $refundRequest->amount = $refundedAmount;

            $this->logger->info('Starting request refund', [
                'status' => $refundStatus,
                'request' => get_object_vars($refundRequest),
            ]);

            $actionId = $paymentHandler->refundPayment($checkoutPaymentId, $refundRequest, $order);
            $orderCustomFields->setLastCheckoutActionId($actionId);

            $this->addRefundedLineItemsToOrder($order, $requestLineItems, $productPromotions, $context);
            $this->updateStockForProduct($requestLineItems, $context);

            $this->orderService->updateCheckoutCustomFields($order, $orderCustomFields, $context);
            $this->orderTransactionService->processTransition($orderTransaction, $refundStatus, $context);
            $this->orderService->processTransition($order, $settings, $refundStatus, $context);
        } catch (Throwable $ex) {
            $message = sprintf('Error while refund payment for order ID: %s, checkoutPaymentId: %s', $order->getOrderNumber(), $checkoutPaymentId);
            $this->logger->error($message, [
                'error' => $ex->getMessage(),
            ]);

            throw new CheckoutComException($message);
        }
    }

    private function addRefundedLineItemsToOrder(
        OrderEntity $order,
        LineItemCollection $requestLineItems,
        ?ProductPromotionCollection $productPromotions,
        Context $context
    ): void {
        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext($order, $context);
        $cart = $this->orderConverter->convertToCart($order, $context);
        $cart->addLineItems($requestLineItems);

        if ($productPromotions instanceof ProductPromotionCollection) {
            $this->updateCartLineItemsByProductPromotions($cart, $productPromotions);
        }

        $recalculatedCart = $this->cartService->recalculateByCart($cart, $salesChannelContext);

        $conversionContext = (new OrderConversionContext())
            ->setIncludeCustomer(false)
            ->setIncludeBillingAddress(false)
            ->setIncludeDeliveries(false)
            ->setIncludeTransactions(false);

        $orderData = $this->orderConverter->convertToOrder($recalculatedCart, $salesChannelContext, $conversionContext);
        $orderData['id'] = $order->getId();

        $this->orderService->updateOrder($orderData, $context);
    }

    private function updateCartLineItemsByProductPromotions(Cart $cart, ProductPromotionCollection $productPromotions): void
    {
        $lineItemsData = [];
        foreach ($productPromotions as $productPromotion) {
            foreach ($productPromotion->getPromotions() as $promotionComposition) {
                if (\array_key_exists($promotionComposition->getLineItemId(), $lineItemsData)) {
                    $lineItemData = $lineItemsData[$promotionComposition->getLineItemId()];
                } else {
                    $lineItemData = [];
                }

                $lineItemData[$promotionComposition->getReferencedId()] = [
                    'referenceId' => $promotionComposition->getReferencedId(),
                    'refundedQuantity' => $promotionComposition->getRefundedQuantity(),
                ];

                $lineItemsData[$promotionComposition->getLineItemId()] = $lineItemData;
            }
        }

        foreach ($lineItemsData as $lineItemId => $lineItemData) {
            $lineItem = $cart->get($lineItemId);
            if (!$lineItem instanceof LineItem) {
                $message = sprintf('Could not find line item within line item ID: %s', $lineItemId);
                $this->logger->critical($message);

                throw new CheckoutComException($message);
            }

            $lineItem->setPayloadValue(RefundBuilder::LINE_ITEM_PROMOTION_PAYLOAD, $lineItemData);
        }
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
