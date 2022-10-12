<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\Transition;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

abstract class AbstractOrderTransactionTransitionService
{
    abstract public function getDecorated(): AbstractOrderTransactionTransitionService;

    abstract public function openTransaction(OrderTransactionEntity $transaction, Context $context): void;

    abstract public function processTransaction(OrderTransactionEntity $transaction, Context $context): void;

    abstract public function payTransaction(OrderTransactionEntity $transaction, Context $context): void;

    abstract public function cancelTransaction(OrderTransactionEntity $transaction, Context $context): void;

    abstract public function failTransaction(OrderTransactionEntity $transaction, Context $context): void;

    abstract public function authorizeTransaction(OrderTransactionEntity $transaction, Context $context): void;

    abstract public function refundTransaction(OrderTransactionEntity $transaction, Context $context): void;

    abstract public function partialRefundTransaction(OrderTransactionEntity $transaction, Context $context): void;
}
