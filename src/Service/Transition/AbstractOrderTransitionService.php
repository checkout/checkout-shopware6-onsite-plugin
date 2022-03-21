<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Transition;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

abstract class AbstractOrderTransitionService
{
    abstract public function getDecorated(): AbstractOrderTransitionService;

    abstract public function setTransitionState(OrderEntity $order, string $transitionState, Context $context): void;

    abstract public function openOrder(OrderEntity $order, Context $context): void;

    abstract public function progressOrder(OrderEntity $order, Context $context): void;

    abstract public function completeOrder(OrderEntity $order, Context $context): void;

    abstract public function cancelOrder(OrderEntity $order, Context $context): void;
}
