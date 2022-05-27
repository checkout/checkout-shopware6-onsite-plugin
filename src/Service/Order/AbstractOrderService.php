<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractOrderService
{
    abstract public function getDecorated(): AbstractOrderService;

    abstract public function getOrder(Context $context, string $orderId, array $associations = []): OrderEntity;

    abstract public function setRequestLastOrderId(string $lastOrderId): void;

    abstract public function getRequestLastOrderId(): ?string;

    abstract public function updateCheckoutCustomFields(OrderEntity $order, OrderCustomFieldsStruct $orderCustomFields, SalesChannelContext $context): void;

    abstract public static function getCheckoutOrderCustomFields(OrderEntity $order): OrderCustomFieldsStruct;

    abstract public function processTransition(OrderEntity $order, SettingStruct $settings, ?string $checkoutPaymentStatus, Context $context): void;
}
