<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

abstract class AbstractOrderTransactionService
{
    abstract public function getDecorated(): AbstractOrderTransactionService;

    abstract public function processTransition(OrderTransactionEntity $transaction, string $checkoutPaymentStatus, Context $context): void;
}
