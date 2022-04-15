<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Extractor;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
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
}
