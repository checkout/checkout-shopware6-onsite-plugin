<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Extractor;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractOrderExtractor
{
    abstract public function getDecorated(): AbstractOrderExtractor;

    abstract public function extractCustomer(OrderEntity $order, SalesChannelContext $context): CustomerEntity;

    abstract public function extractCurrency(OrderEntity $order): CurrencyEntity;
}
