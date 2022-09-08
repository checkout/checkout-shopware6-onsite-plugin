<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Builder;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Struct\LineItem\LineItemPayload;
use CheckoutCom\Shopware6\Struct\LineItem\ProductPromotion;
use CheckoutCom\Shopware6\Struct\LineItem\ProductPromotionCollection;
use CheckoutCom\Shopware6\Struct\LineItem\PromotionComposition;
use CheckoutCom\Shopware6\Struct\LineItem\PromotionCompositionCollection;
use CheckoutCom\Shopware6\Struct\Request\Refund\OrderRefundRequest;
use CheckoutCom\Shopware6\Struct\Request\Refund\RefundItemRequest;
use CheckoutCom\Shopware6\Struct\Request\Refund\RefundItemRequestCollection;
use CheckoutCom\Shopware6\Struct\WebhookReceiveDataStruct;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;

class RefundBuilder
{
    public const LINE_ITEM_PAYLOAD = 'checkoutComPayments';
    public const LINE_ITEM_PROMOTION_PAYLOAD = 'checkoutComPromotions';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $loggerService)
    {
        $this->logger = $loggerService;
    }

    /**
     * Build line items for full refund then later it will be inserted to order line items
     */
    public function buildRefundRequestForFullRefund(OrderEntity $order): OrderRefundRequest
    {
        $orderLineItems = $order->getLineItems();
        if (!$orderLineItems instanceof OrderLineItemCollection) {
            $message = sprintf('The orderLineItems must be instance of OrderLineItemCollection with Order ID: %s', $order->getId());
            $this->logger->warning($message);

            throw new CheckoutComException($message);
        }

        $refundItems = new RefundItemRequestCollection();
        foreach ($orderLineItems as $orderLineItem) {
            $lineItemPayload = $this->getCheckoutLineItemPayload($orderLineItem);
            // Skip if the current order line item have `refund line item ID`
            if (!empty($lineItemPayload->getRefundLineItemId())) {
                continue;
            }

            // Count total remaining quantity
            $remainingQuantity = $orderLineItems->reduce(
                function (int $remainingQuantity, OrderLineItemEntity $orderRefundLineItem) use ($orderLineItem) {
                    $orderRefundPayload = $this->getCheckoutLineItemPayload($orderRefundLineItem);

                    if ($orderRefundPayload->getRefundLineItemId() !== $orderLineItem->getId()) {
                        return $remainingQuantity;
                    }

                    return $remainingQuantity - $orderRefundLineItem->getQuantity();
                },
                $orderLineItem->getQuantity()
            );

            if ($remainingQuantity === 0) {
                continue;
            }

            $refundItem = new RefundItemRequest();
            $refundItem->setId($orderLineItem->getId());
            $refundItem->setReturnQuantity($remainingQuantity);
            $refundItems->set($refundItem->getId(), $refundItem);
        }

        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId($order->getId());
        $orderRefundRequest->setItems($refundItems);

        return $orderRefundRequest;
    }

    /**
     * Build line items then later it will be inserted to order line items
     */
    public function buildLineItems(
        RefundItemRequestCollection $refundItems,
        OrderEntity $order,
        CurrencyEntity $currency,
        ProductPromotionCollection $productPromotions
    ): LineItemCollection {
        $orderLineItems = $order->getLineItems();
        if (!$orderLineItems instanceof OrderLineItemCollection) {
            $message = sprintf('The orderLineItems must be instance of OrderLineItemCollection with Order ID: %s', $order->getId());
            $this->logger->warning($message);

            throw new CheckoutComException($message);
        }

        $lineItems = new LineItemCollection();
        foreach ($orderLineItems as $orderLineItem) {
            $refundItem = $refundItems->get($orderLineItem->getId());
            if (!$refundItem instanceof RefundItemRequest) {
                continue;
            }

            $canRefundItem = $this->canRefundItem($orderLineItems, $orderLineItem, $refundItem);
            if (!$canRefundItem) {
                $message = sprintf('Can not refund within orderLineItem ID: %s', $orderLineItem->getId());
                $this->logger->error($message);

                throw new CheckoutComException($message, [
                    'refundRequest' => $refundItems->jsonSerialize(),
                ]);
            }

            $productPromotion = $productPromotions->get($orderLineItem->getReferencedId());

            if ($productPromotion instanceof ProductPromotion) {
                $this->buildLineItemsByPromotions(
                    $lineItems,
                    $refundItem,
                    $orderLineItem,
                    $currency,
                    $productPromotion
                );

                continue;
            }

            $this->buildLineItem(
                $lineItems,
                $refundItem,
                $orderLineItem,
                $currency
            );
        }

        return $lineItems;
    }

    /**
     * Build line items by webhook data then later it will be inserted to order line items
     */
    public function buildLineItemsForWebhook(WebhookReceiveDataStruct $receiveDataStruct): LineItemCollection
    {
        $webhookAmount = $receiveDataStruct->getAmount();
        if ($webhookAmount === null) {
            $message = sprintf('The amount of webhook can not null for webhook ID: %s', $receiveDataStruct->getId());
            $this->logger->warning($message);

            throw new CheckoutComException($message);
        }

        $webhookCurrency = $receiveDataStruct->getCurrency();
        if ($webhookCurrency === null) {
            $message = sprintf('The currency of webhook can not null for webhook ID: %s', $receiveDataStruct->getId());
            $this->logger->warning($message);

            throw new CheckoutComException($message);
        }

        $unitPrice = CheckoutComUtil::formatPriceShopware(
            $webhookAmount,
            $webhookCurrency
        ) * -1;
        $refundQuantity = 1;

        $lineItem = new LineItem(
            Uuid::randomHex(),
            LineItem::CUSTOM_LINE_ITEM_TYPE,
            null,
            $refundQuantity
        );

        $lineItem->setStackable(true);
        $lineItem->setRemovable(true);
        $lineItem->setLabel('Refunded from Checkout hub');
        $lineItem->setPriceDefinition(
            new QuantityPriceDefinition(
                $unitPrice,
                new TaxRuleCollection(),
                $refundQuantity
            )
        );
        $lineItem->setPrice(
            new CalculatedPrice(
                $unitPrice,
                $unitPrice * $refundQuantity,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                $refundQuantity,
            )
        );

        $lineItemPayload = new LineItemPayload();
        $lineItemPayload->setType(LineItemPayload::LINE_ITEM_WEBHOOK);
        $lineItem->setPayloadValue(self::LINE_ITEM_PAYLOAD, $lineItemPayload->jsonSerialize());

        return new LineItemCollection([$lineItem]);
    }

    /**
     * Build line items by fix price difference then later it will be inserted to order line items
     */
    public function buildLineItemsForFixPriceDifference(float $price): LineItemCollection
    {
        $unitPrice = $price * -1;
        $refundQuantity = 1;

        $lineItem = new LineItem(
            Uuid::randomHex(),
            LineItem::CUSTOM_LINE_ITEM_TYPE,
            null,
            $refundQuantity
        );

        $lineItem->setStackable(true);
        $lineItem->setRemovable(true);
        $lineItem->setLabel('Fix price difference');
        $lineItem->setPriceDefinition(
            new QuantityPriceDefinition(
                $unitPrice,
                new TaxRuleCollection(),
                $refundQuantity
            )
        );
        $lineItem->setPrice(
            new CalculatedPrice(
                $unitPrice,
                $unitPrice * $refundQuantity,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                $refundQuantity,
            )
        );

        $lineItemPayload = new LineItemPayload();
        $lineItemPayload->setType(LineItemPayload::LINE_ITEM_FIX_PRICE);
        $lineItem->setPayloadValue(self::LINE_ITEM_PAYLOAD, $lineItemPayload->jsonSerialize());

        return new LineItemCollection([$lineItem]);
    }

    /**
     * Build line items by shipping costs then later it will be inserted to order line items
     */
    public function buildLineItemsShippingCosts(OrderEntity $order): LineItemCollection
    {
        $orderDeliveries = $order->getDeliveries();
        if (!$orderDeliveries instanceof OrderDeliveryCollection) {
            $this->logger->critical(
                sprintf('The $orderDeliveries must be instance of OrderDeliveryCollection with Order ID: %s', $order->getId())
            );

            throw new InvalidOrderException($order->getId());
        }

        $lineItems = new LineItemCollection();
        foreach ($orderDeliveries as $orderDelivery) {
            $lineItem = $this->buildLineItemShippingCosts($orderDelivery);
            $lineItems->add($lineItem);
        }

        return $lineItems;
    }

    /**
     * Get the line item payload struct
     */
    public function getCheckoutLineItemPayload(Struct $lineItem): LineItemPayload
    {
        if (!$lineItem instanceof LineItem && !$lineItem instanceof OrderLineItemEntity) {
            $message = 'The $lineItem must be instance of LineItem or OrderLineItemEntity';
            $this->logger->critical($message);

            throw new CheckoutComException($message);
        }

        $payload = $lineItem->getPayload() ?? [];

        $lineItemPayload = new LineItemPayload();
        $lineItemPayload->assign($payload[self::LINE_ITEM_PAYLOAD] ?? []);

        return $lineItemPayload;
    }

    /**
     * Check the request is able to refund or not by line item
     * Only allow if:
     *    Order line item's quantity - refunded quantity >= request quantity
     */
    public function canRefundItem(
        OrderLineItemCollection $orderLineItemCollection,
        OrderLineItemEntity $orderLineItem,
        RefundItemRequest $refundItem
    ): bool {
        $lineItemPayload = $this->getCheckoutLineItemPayload($orderLineItem);

        if (!empty($lineItemPayload->getRefundLineItemId())) {
            // If the line item has line-item ID, it means the line-item is refunded line item,
            // and it is not allow to refund this item
            return false;
        }

        // Filter the refunded line items from order
        $refundOrderLineItems = $orderLineItemCollection->filter(function (OrderLineItemEntity $orderLineItem) use ($refundItem) {
            $lineItemPayload = $this->getCheckoutLineItemPayload($orderLineItem);

            return $lineItemPayload->getRefundLineItemId() === $refundItem->getId();
        });

        $refundedQuantity = $refundOrderLineItems->reduce(function (int $totalRefunded, OrderLineItemEntity $orderLineItem) {
            return $totalRefunded + $orderLineItem->getQuantity();
        }, 0);

        return ($orderLineItem->getQuantity() - $refundedQuantity) >= $refundItem->getReturnQuantity();
    }

    /**
     * Build line items by provide product promotions then later it will be inserted to order line items
     */
    public function buildLineItemsByPromotions(
        LineItemCollection $lineItems,
        RefundItemRequest $refundItem,
        OrderLineItemEntity $orderLineItem,
        CurrencyEntity $currency,
        ProductPromotion $productPromotion
    ): void {
        $refundItem->setRemainingReturnQuantity($refundItem->getReturnQuantity());

        // Group promotion composition by quantity
        $groupPromotions = $productPromotion->getPromotions()->groupByRemainingQuantity();
        krsort($groupPromotions);

        $totalDiscountPerQuantity = 0.0;
        $discountCompositions = [];

        $quantitiesKeys = array_keys($groupPromotions);

        // Build line items depend on promotion composition quantity
        foreach ($groupPromotions as $remainingQuantity => $quantityPromotionCompositions) {
            $discountPerQuantity = 0.0;

            // Calculate total discount per quantity
            foreach ($quantityPromotionCompositions as $promotionComposition) {
                $discountPerQuantity += $promotionComposition->getDiscountPerQuantity();

                // Update new refunded quantity
                $promotionComposition->setRefundedQuantity(
                    min(
                        $promotionComposition->getRefundedQuantity() + $refundItem->getReturnQuantity(),
                        $remainingQuantity,
                    )
                );

                $discountCompositions[] = [
                    'label' => $promotionComposition->getLabel(),
                ];
            }

            $totalDiscountPerQuantity += $discountPerQuantity;
            $nextPromotionRemainingQuantity = (int) ($quantitiesKeys[array_search($remainingQuantity, $quantitiesKeys, true) + 1] ?? 0);

            // Calculate remaining quantity for new line item
            $currentRemainingQuantity = $refundItem->getRemainingReturnQuantity() - $nextPromotionRemainingQuantity;
            if ($currentRemainingQuantity <= 0) {
                continue;
            }

            $refundItem->setReturnQuantity($currentRemainingQuantity);

            // Update remaining return quantity for next loop checking
            $refundItem->setRemainingReturnQuantity($nextPromotionRemainingQuantity);

            $this->buildLineItem(
                $lineItems,
                $refundItem,
                $orderLineItem,
                $currency,
                $totalDiscountPerQuantity,
                $discountCompositions
            );
        }
    }

    public function buildLineItem(
        LineItemCollection $lineItems,
        RefundItemRequest $refundItem,
        OrderLineItemEntity $orderLineItem,
        CurrencyEntity $currency,
        float $discountPerQuantity = 0.0,
        array $discountCompositions = []
    ): void {
        if ($orderLineItem->getUnitPrice() < 0) {
            $message = sprintf('The $orderUnitPrice must equal or greater than 0: %s', $orderLineItem->getId());
            $this->logger->warning($message);

            throw new CheckoutComException($message);
        }

        $orderPrice = $orderLineItem->getPrice();
        if (!$orderPrice instanceof CalculatedPrice) {
            $message = sprintf('The $orderPrice must be instance of CalculatedPrice with LineItem ID: %s', $orderLineItem->getId());
            $this->logger->warning($message);

            throw new CheckoutComException($message);
        }

        $unitPrice = CheckoutComUtil::floorp(
            $orderLineItem->getUnitPrice() - $discountPerQuantity,
            $currency->getItemRounding()->getDecimals()
        ) * -1;
        $refundQuantity = $refundItem->getReturnQuantity();

        $lineItem = new LineItem(
            Uuid::randomHex(),
            LineItem::CUSTOM_LINE_ITEM_TYPE,
            null,
            $refundItem->getReturnQuantity()
        );

        $lineItem->setStackable(true);
        $lineItem->setRemovable(true);
        $lineItem->setLabel($orderLineItem->getLabel());
        $lineItem->setPriceDefinition(
            new QuantityPriceDefinition(
                $unitPrice,
                new TaxRuleCollection(),
                $refundQuantity
            )
        );
        $lineItem->setPrice(
            new CalculatedPrice(
                $unitPrice,
                $unitPrice * $refundQuantity,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                $refundQuantity,
            )
        );

        $lineItemPayload = new LineItemPayload();
        $lineItemPayload->setType(LineItemPayload::LINE_ITEM_PRODUCT);
        $lineItemPayload->setRefundLineItemId($orderLineItem->getId());
        $lineItemPayload->setProductId($orderLineItem->getProductId());
        $lineItemPayload->setProductId($orderLineItem->getProductId());
        $lineItemPayload->setDiscountCompositions($discountCompositions);
        $lineItem->setPayloadValue(self::LINE_ITEM_PAYLOAD, $lineItemPayload->jsonSerialize());

        $lineItems->add($lineItem);
    }

    /**
     * Build product promotions for refund stuffs
     * Need it for calculate discount of each product
     */
    public function buildProductPromotions(OrderEntity $order): ProductPromotionCollection
    {
        // Init list of product promotions
        $productPromotions = new ProductPromotionCollection();
        $lineItems = $order->getLineItems();

        if (!$lineItems instanceof OrderLineItemCollection) {
            $message = sprintf('The $lineItems must be instance of OrderLineItemCollection with order number: %s', $order->getOrderNumber());
            $this->logger->warning($message);

            throw new CheckoutComException($message);
        }

        foreach ($lineItems as $item) {
            $compositions = $item->getPayload()['composition'] ?? [];
            if (empty($compositions) || empty($item->getPromotionId())) {
                continue;
            }

            foreach ($compositions as $composition) {
                $productId = $composition['id'];

                $checkoutPayload = $item->getPayload()[self::LINE_ITEM_PROMOTION_PAYLOAD] ?? [];
                $lineItemRefundedPayload = $checkoutPayload[$productId] ?? [];
                $refundedQuantityPayload = $lineItemRefundedPayload['refundedQuantity'] ?? 0;

                $remainingQuantity = (int) $composition['quantity'] - (int) $refundedQuantityPayload;

                if ($remainingQuantity === 0) {
                    continue;
                }

                $discountPerQuantity = (float) $composition['discount'] / (int) $composition['quantity'];

                // Create a product promotion instance if it is not exists in the product promotion list
                // Also get all discount promotion list in the product promotion to add the current loop composition to the list
                $productPromotion = $productPromotions->get($productId);
                if ($productPromotion instanceof ProductPromotion) {
                    $promotionCompositions = $productPromotion->getPromotions();
                } else {
                    $productPromotion = new ProductPromotion();
                    $productPromotion->setProductId($productId);
                    $promotionCompositions = new PromotionCompositionCollection();
                }

                $promotionComposition = new PromotionComposition();
                $promotionComposition->setLineItemId($item->getIdentifier());
                $promotionComposition->setReferencedId($productId);
                $promotionComposition->setLabel($item->getLabel());
                $promotionComposition->setRefundedQuantity($refundedQuantityPayload);
                $promotionComposition->setDiscountPerQuantity($discountPerQuantity);
                $promotionComposition->setRemainingQuantity($remainingQuantity);

                $promotionCompositions->add($promotionComposition);

                $productPromotion->setPromotions($promotionCompositions);

                $productPromotions->set(
                    $productPromotion->getProductId(),
                    $productPromotion,
                );
            }
        }

        return $productPromotions;
    }

    private function buildLineItemShippingCosts(OrderDeliveryEntity $orderDelivery): LineItem
    {
        $shippingMethod = $orderDelivery->getShippingMethod();
        if (!$shippingMethod instanceof ShippingMethodEntity) {
            $message = sprintf('The $shippingMethod must be instance of ShippingMethodEntity with OrderDelivery ID: %s', $orderDelivery->getId());
            $this->logger->warning($message);

            throw new CheckoutComException($message);
        }

        $shippingCosts = $orderDelivery->getShippingCosts();
        $unitPrice = $shippingCosts->getUnitPrice() * -1;

        $lineItem = new LineItem(
            Uuid::randomHex(),
            LineItem::CUSTOM_LINE_ITEM_TYPE,
            null,
            $shippingCosts->getQuantity()
        );

        $lineItem->setStackable(true);
        $lineItem->setRemovable(true);
        $lineItem->setLabel($shippingMethod->getName());
        $lineItem->setPriceDefinition(
            new QuantityPriceDefinition(
                $unitPrice,
                new TaxRuleCollection(),
                $shippingCosts->getQuantity()
            )
        );
        $lineItem->setPrice(
            new CalculatedPrice(
                $unitPrice,
                $unitPrice * $shippingCosts->getQuantity(),
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                $shippingCosts->getQuantity(),
            )
        );

        $lineItemPayload = new LineItemPayload();
        $lineItemPayload->setType(LineItemPayload::LINE_ITEM_SHIPPING);
        $lineItem->setPayloadValue(self::LINE_ITEM_PAYLOAD, $lineItemPayload->jsonSerialize());

        return $lineItem;
    }
}
