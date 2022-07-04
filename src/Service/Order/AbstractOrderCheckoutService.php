<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Shopware\Core\Framework\Context;

abstract class AbstractOrderCheckoutService
{
    abstract public function getDecorated(): AbstractOrderCheckoutService;

    abstract public function getCheckoutPayment(string $orderId, Context $context): Payment;

    abstract public function capturePayment(string $orderId, Context $context): void;

    abstract public function voidPayment(string $orderId, Context $context): void;
}
