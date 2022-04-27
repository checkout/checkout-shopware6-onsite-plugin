<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use CheckoutCom\Shopware6\Struct\SettingStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

abstract class AbstractOrderService
{
    abstract public function getDecorated(): AbstractOrderService;

    abstract public function getOrder(string $orderId, Context $context): OrderEntity;

    abstract public function setRequestLastOrderId(string $lastOrderId): void;

    abstract public function getRequestLastOrderId(): ?string;

    abstract public static function getCheckoutOrderCustomFields(OrderEntity $order): OrderCustomFieldsStruct;

    abstract public function processTransition(OrderEntity $order, SettingStruct $settings, string $checkoutPaymentStatus, Context $context): void;
}
