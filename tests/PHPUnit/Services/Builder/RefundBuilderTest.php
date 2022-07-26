<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Builder;

use Checkout\Common\Currency;
use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Service\Builder\RefundBuilder;
use CheckoutCom\Shopware6\Struct\LineItem\LineItemPayload;
use CheckoutCom\Shopware6\Struct\Request\Refund\OrderRefundRequest;
use CheckoutCom\Shopware6\Struct\Request\Refund\RefundItemRequest;
use CheckoutCom\Shopware6\Struct\Request\Refund\RefundItemRequestCollection;
use CheckoutCom\Shopware6\Struct\WebhookReceiveDataStruct;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

class RefundBuilderTest extends TestCase
{
    private RefundBuilder $refundBuilder;

    protected function setUp(): void
    {
        $this->refundBuilder = new RefundBuilder(
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testBuildRefundRequestForFullRefundOfNullOrderLineItemCollection(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => null,
        ]);

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildRefundRequestForFullRefund($order);
    }

    public function testBuildRefundRequestForFullRefundOfEmptyOrderLineItemCollection(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => $this->createMock(OrderLineItemCollection::class),
        ]);

        $result = $this->refundBuilder->buildRefundRequestForFullRefund($order);
        static::assertInstanceOf(OrderRefundRequest::class, $result);
        static::assertSame(0, $result->getItems()->count());
    }

    public function testBuildRefundRequestForFullRefundOfSkipIfHaveRefundLineItemId(): void
    {
        $lineItemPayload = $this->getLineItemPayload('foo');

        $skipOrderLineItem = new OrderLineItemEntity();
        $skipOrderLineItem->setId('foo');
        $skipOrderLineItem->setPayload([
            RefundBuilder::LINE_ITEM_PAYLOAD => $lineItemPayload->jsonSerialize(),
        ]);

        $continueOrderLineItem = new OrderLineItemEntity();
        $continueOrderLineItem->setId('bar');
        $continueOrderLineItem->setQuantity(1);

        $orderLineItems = new OrderLineItemCollection([$skipOrderLineItem, $continueOrderLineItem]);

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => $orderLineItems,
        ]);

        $result = $this->refundBuilder->buildRefundRequestForFullRefund($order);
        static::assertInstanceOf(OrderRefundRequest::class, $result);
        static::assertSame(1, $result->getItems()->count());
        static::assertSame('bar', $result->getItems()->first()->getId());
    }

    public function testBuildRefundRequestForFullRefundOfRemainingQuantityEqual0(): void
    {
        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId('foo');
        $orderLineItem->setQuantity(0);

        $orderLineItems = new OrderLineItemCollection([$orderLineItem]);

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => $orderLineItems,
        ]);

        $result = $this->refundBuilder->buildRefundRequestForFullRefund($order);
        static::assertInstanceOf(OrderRefundRequest::class, $result);
        static::assertSame(0, $result->getItems()->count());
    }

    public function testBuildRefundRequestForFullRefundSuccessful(): void
    {
        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId('foo');
        $orderLineItem->setQuantity(1);

        $orderLineItems = new OrderLineItemCollection([$orderLineItem]);

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => $orderLineItems,
        ]);

        $result = $this->refundBuilder->buildRefundRequestForFullRefund($order);
        static::assertInstanceOf(OrderRefundRequest::class, $result);
        static::assertSame(1, $result->getItems()->count());
    }

    public function testBuildLineItemsOfNotFoundOrderLineItems(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => null,
        ]);

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildLineItems(new RefundItemRequestCollection(), $order);
    }

    public function testBuildLineItemsOfCannotRefundBecauseOfHasRefundLineId(): void
    {
        $refundLineId = 'foo';

        $lineItemPayload = $this->getLineItemPayload($refundLineId);

        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId($refundLineId);
        $orderLineItem->setPayload([
            RefundBuilder::LINE_ITEM_PAYLOAD => $lineItemPayload->jsonSerialize(),
        ]);

        $refundItemRequest = new RefundItemRequest();
        $refundItemRequest->setId($refundLineId);

        $refundItems = new RefundItemRequestCollection();
        $refundItems->set($refundItemRequest->getId(), $refundItemRequest);

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => new OrderLineItemCollection([$orderLineItem]),
        ]);

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildLineItems($refundItems, $order);
    }

    public function testBuildLineItemsOfCannotBuildBecauseOfUnitPriceLessThan0(): void
    {
        $refundLineId = 'foo';

        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId($refundLineId);
        $orderLineItem->setQuantity(1);
        $orderLineItem->setUnitPrice(-5);

        $refundItemRequest = new RefundItemRequest();
        $refundItemRequest->setId($refundLineId);
        $refundItemRequest->setReturnQuantity(1);

        $refundItems = new RefundItemRequestCollection();
        $refundItems->set($refundItemRequest->getId(), $refundItemRequest);

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => new OrderLineItemCollection([$orderLineItem]),
        ]);

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildLineItems($refundItems, $order);
    }

    public function testBuildLineItemsOfCannotBuildBecauseOfNullPrice(): void
    {
        $refundLineId = 'foo';

        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId($refundLineId);
        $orderLineItem->setQuantity(1);
        $orderLineItem->setUnitPrice(5);

        $refundItemRequest = new RefundItemRequest();
        $refundItemRequest->setId($refundLineId);
        $refundItemRequest->setReturnQuantity(1);

        $refundItems = new RefundItemRequestCollection();
        $refundItems->set($refundItemRequest->getId(), $refundItemRequest);

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => new OrderLineItemCollection([$orderLineItem]),
        ]);

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildLineItems($refundItems, $order);
    }

    public function testBuildLineItemsSuccessful(): void
    {
        $refundLineId = 'foo';

        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId($refundLineId);
        $orderLineItem->setQuantity(1);
        $orderLineItem->setUnitPrice(5);
        $orderLineItem->setLabel('foo');
        $orderLineItem->setPrice(
            new CalculatedPrice(
                5,
                5,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                1
            )
        );

        $refundItemRequest = new RefundItemRequest();
        $refundItemRequest->setId($refundLineId);
        $refundItemRequest->setReturnQuantity(1);

        $refundItems = new RefundItemRequestCollection();
        $refundItems->set($refundItemRequest->getId(), $refundItemRequest);

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getLineItems' => new OrderLineItemCollection([$orderLineItem]),
        ]);

        $result = $this->refundBuilder->buildLineItems($refundItems, $order);
        static::assertInstanceOf(LineItemCollection::class, $result);
    }

    public function testBuildLineItemsShippingCostsOfNullOrderDeliveryCollection(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getDeliveries' => null,
        ]);

        static::expectException(InvalidOrderException::class);

        $this->refundBuilder->buildLineItemsShippingCosts($order);
    }

    public function testBuildLineItemsShippingCostsOfCannotBuildBecauseOfNullShippingMethod(): void
    {
        $orderDelivery = new OrderDeliveryEntity();
        $orderDelivery->setId('foo');

        $lineItem = new LineItem('foo', LineItem::CUSTOM_LINE_ITEM_TYPE);

        $lineItems = new LineItemCollection();
        $lineItems->add($lineItem);

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getDeliveries' => new OrderDeliveryCollection([$orderDelivery]),
        ]);

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildLineItemsShippingCosts($order);
    }

    public function testBuildLineItemsShippingCostsSuccessful(): void
    {
        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId('foo');

        $orderDelivery = new OrderDeliveryEntity();
        $orderDelivery->setId('foo');
        $orderDelivery->setShippingMethod($shippingMethod);
        $orderDelivery->setShippingCosts(
            new CalculatedPrice(
                5,
                5,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                1
            )
        );

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => 'foo',
            'getDeliveries' => new OrderDeliveryCollection([$orderDelivery]),
        ]);

        $result = $this->refundBuilder->buildLineItemsShippingCosts($order);
        static::assertInstanceOf(LineItemCollection::class, $result);
    }

    public function testGetCheckoutLineItemPayloadOfInvalidParam(): void
    {
        static::expectException(CheckoutComException::class);
        $this->refundBuilder->getCheckoutLineItemPayload(new OrderEntity());
    }

    public function testGetCheckoutLineItemPayloadSuccessful(): void
    {
        $lineItem = new LineItem('foo', LineItem::CUSTOM_LINE_ITEM_TYPE);
        $result = $this->refundBuilder->getCheckoutLineItemPayload($lineItem);
        static::assertInstanceOf(LineItemPayload::class, $result);
    }

    public function testCanRefundItemOfHasRefundLineItemId(): void
    {
        $lineItemPayload = $this->getLineItemPayload('foo');

        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId('foo');
        $orderLineItem->setPayload([
            RefundBuilder::LINE_ITEM_PAYLOAD => $lineItemPayload->jsonSerialize(),
        ]);

        $result = $this->refundBuilder->canRefundItem(
            new OrderLineItemCollection(),
            $orderLineItem,
            new RefundItemRequest()
        );
        static::assertFalse($result);
    }

    public function testCanRefundItemSuccessful(): void
    {
        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId('foo');
        $orderLineItem->setQuantity(5);

        $refundItemRequest = new RefundItemRequest();
        $refundItemRequest->setReturnQuantity(5);

        $result = $this->refundBuilder->canRefundItem(
            new OrderLineItemCollection(),
            $orderLineItem,
            $refundItemRequest
        );
        static::assertTrue($result);
    }

    public function testBuildLineItemOfUnitPriceLessThan0(): void
    {
        $refundLineId = 'foo';

        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId($refundLineId);
        $orderLineItem->setQuantity(1);
        $orderLineItem->setUnitPrice(-5);

        $refundItemRequest = new RefundItemRequest();
        $refundItemRequest->setId($refundLineId);
        $refundItemRequest->setReturnQuantity(1);

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildLineItem($refundItemRequest, $orderLineItem);
    }

    public function testBuildLineItemOfNullCalculatedPrice(): void
    {
        $refundLineId = 'foo';

        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId($refundLineId);
        $orderLineItem->setQuantity(1);
        $orderLineItem->setUnitPrice(5);

        $refundItemRequest = new RefundItemRequest();
        $refundItemRequest->setId($refundLineId);
        $refundItemRequest->setReturnQuantity(1);

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildLineItem($refundItemRequest, $orderLineItem);
    }

    public function testBuildLineItemSuccessful(): void
    {
        $refundLineId = 'foo';

        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId($refundLineId);
        $orderLineItem->setQuantity(1);
        $orderLineItem->setUnitPrice(5);
        $orderLineItem->setLabel('foo');
        $orderLineItem->setPrice(
            new CalculatedPrice(
                5,
                5,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                1
            )
        );

        $refundItemRequest = new RefundItemRequest();
        $refundItemRequest->setId($refundLineId);
        $refundItemRequest->setReturnQuantity(1);

        $result = $this->refundBuilder->buildLineItem($refundItemRequest, $orderLineItem);
        static::assertInstanceOf(LineItem::class, $result);
        static::assertSame($refundItemRequest->getReturnQuantity(), $result->getQuantity());
    }

    public function testBuildLineItemsForWebhookOfNullWebhookAmount(): void
    {
        $receiveData = new WebhookReceiveDataStruct();
        $receiveData->setId('foo');

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildLineItemsForWebhook($receiveData);
    }

    public function testBuildLineItemsForWebhookOfNullWebhookCurrency(): void
    {
        $receiveData = new WebhookReceiveDataStruct();
        $receiveData->setId('foo');
        $receiveData->setAmount(500);

        static::expectException(CheckoutComException::class);

        $this->refundBuilder->buildLineItemsForWebhook($receiveData);
    }

    public function testBuildLineItemsForWebhookSuccessful(): void
    {
        $receiveData = new WebhookReceiveDataStruct();
        $receiveData->setId('foo');
        $receiveData->setAmount(500);
        $receiveData->setCurrency(Currency::$EUR);

        $result = $this->refundBuilder->buildLineItemsForWebhook($receiveData);

        static::assertInstanceOf(LineItemCollection::class, $result);
        static::assertSame(-5.0, $result->getPrices()->sum()->getTotalPrice());
    }

    private function getLineItemPayload(string $lineItemId): LineItemPayload
    {
        $lineItemPayload = new LineItemPayload();
        $lineItemPayload->setRefundLineItemId($lineItemId);

        return $lineItemPayload;
    }
}
