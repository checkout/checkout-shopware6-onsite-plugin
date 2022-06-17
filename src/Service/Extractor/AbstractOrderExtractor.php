<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Extractor;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractOrderExtractor
{
    abstract public function getDecorated(): AbstractOrderExtractor;

    abstract public function extractOrderNumber(OrderEntity $order): string;

    abstract public function extractCustomer(OrderEntity $order): OrderCustomerEntity;

    abstract public function extractBillingAddress(OrderEntity $order, SalesChannelContext $context): OrderAddressEntity;

    abstract public function extractShippingAddress(OrderEntity $order, SalesChannelContext $context): OrderAddressEntity;

    abstract public function extractCurrency(OrderEntity $order): CurrencyEntity;

    abstract public function extractOrderLineItems(OrderEntity $order): OrderLineItemCollection;

    abstract public function extractOrderDelivery(OrderEntity $order): OrderDeliveryEntity;

    abstract public function extractOrderShippingMethod(OrderEntity $order): ShippingMethodEntity;
}
