<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Struct\SettingStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

abstract class AbstractOrderService
{
    abstract public function getDecorated(): AbstractOrderService;

    abstract public function processTransition(OrderEntity $order, SettingStruct $settings, string $checkoutPaymentStatus, Context $context): void;
}
