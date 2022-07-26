<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;

abstract class AbstractOrderService
{
    abstract public function getDecorated(): AbstractOrderService;

    abstract public function getOrder(Context $context, string $orderId, array $associations = [], ?callable $criteriaCallback = null): OrderEntity;

    abstract public function getOrderByOrderNumber(string $orderNumber, Context $context): OrderEntity;

    abstract public function setRequestLastOrderId(string $lastOrderId): void;

    abstract public function getRequestLastOrderId(): ?string;

    abstract public function createOrder(
        SalesChannelContext $context,
        DataBag $data,
        RequestDataBag $shippingContact,
        SalutationEntity $salutation,
        CountryEntity $country,
        ?CountryStateEntity $countryState
    ): OrderEntity;

    abstract public function updateOrder(array $data, Context $context): void;

    abstract public function updateCheckoutCustomFields(OrderEntity $order, OrderCustomFieldsStruct $orderCustomFields, Context $context): void;

    abstract public static function getCheckoutOrderCustomFields(OrderEntity $order): OrderCustomFieldsStruct;

    abstract public function processTransition(OrderEntity $order, SettingStruct $settings, ?string $checkoutPaymentStatus, Context $context): void;

    abstract public function isOnlyHaveShippingCosts(
        OrderEntity $order,
        LineItemCollection $requestLineItems,
        LineItemCollection $shippingCostsLineItems
    ): bool;
}
