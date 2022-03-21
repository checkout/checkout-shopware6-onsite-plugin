<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Transition;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

class OrderTransactionTransitionService extends AbstractOrderTransactionTransitionService
{
    private LoggerInterface $logger;

    private TransitionService $transitionService;

    public function __construct(LoggerInterface $logger, TransitionService $transitionService)
    {
        $this->logger = $logger;
        $this->transitionService = $transitionService;
    }

    public function getDecorated(): AbstractOrderTransactionTransitionService
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Transition the order transaction to `open` state
     */
    public function openTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        $targetStatuses = [
            OrderTransactionStates::STATE_OPEN,
        ];

        if ($this->isFinalOrTargetStatus($transaction->getStateMachineState(), $targetStatuses)) {
            return;
        }

        if (!$this->allowedTransition($transaction, StateMachineTransitionActions::ACTION_REOPEN, $context)) {
            $this->logger->error(
                sprintf(
                    'It is not allowed to change status to open from %s. Aborting reopen transition',
                    $transaction->getStateMachineState()->getName()
                )
            );

            return;
        }

        $this->transition($transaction, StateMachineTransitionActions::ACTION_REOPEN, $context);
    }

    /**
     * Transition the order transaction to `paid` state
     */
    public function processTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        $targetStatuses = [
            OrderTransactionStates::STATE_IN_PROGRESS,
        ];

        if ($this->isFinalOrTargetStatus($transaction->getStateMachineState(), $targetStatuses)) {
            return;
        }

        if (!$this->allowedTransition($transaction, StateMachineTransitionActions::ACTION_DO_PAY, $context)) {
            $this->openTransaction($transaction, $context);
        }

        $this->transition($transaction, StateMachineTransitionActions::ACTION_DO_PAY, $context);
    }

    /**
     * Transition the order transaction to `in-process` state
     */
    public function payTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        $targetStatuses = [
            OrderTransactionStates::STATE_PAID,
        ];

        if ($this->isFinalOrTargetStatus($transaction->getStateMachineState(), $targetStatuses)) {
            return;
        }

        if (!$this->allowedTransition($transaction, StateMachineTransitionActions::ACTION_PAID, $context)) {
            $this->openTransaction($transaction, $context);
        }

        $this->transition($transaction, StateMachineTransitionActions::ACTION_PAID, $context);
    }

    /**
     * Transition the order transaction to `cancelled` state
     */
    public function cancelTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        $targetStatuses = [
            OrderTransactionStates::STATE_CANCELLED,
        ];

        if ($this->isFinalOrTargetStatus($transaction->getStateMachineState(), $targetStatuses)) {
            return;
        }

        if (!$this->allowedTransition($transaction, StateMachineTransitionActions::ACTION_CANCEL, $context)) {
            $this->openTransaction($transaction, $context);
        }

        $this->transition($transaction, StateMachineTransitionActions::ACTION_CANCEL, $context);
    }

    /**
     * Transition the order transaction to `failed` state
     */
    public function failTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        $targetStatuses = [
            OrderTransactionStates::STATE_CANCELLED,
            OrderTransactionStates::STATE_FAILED,
        ];

        if ($this->isFinalOrTargetStatus($transaction->getStateMachineState(), $targetStatuses)) {
            return;
        }

        if (!$this->allowedTransition($transaction, StateMachineTransitionActions::ACTION_FAIL, $context)) {
            $this->openTransaction($transaction, $context);
        }

        $this->transition($transaction, StateMachineTransitionActions::ACTION_FAIL, $context);
    }

    /**
     * Transition the order transaction to `authorized` state
     */
    public function authorizeTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        $targetStatuses = [
            OrderTransactionStates::STATE_AUTHORIZED,
            OrderTransactionStates::STATE_PAID,
        ];

        if ($this->isFinalOrTargetStatus($transaction->getStateMachineState(), $targetStatuses)) {
            return;
        }

        if (!$this->allowedTransition($transaction, StateMachineTransitionActions::ACTION_AUTHORIZE, $context)) {
            $this->openTransaction($transaction, $context);
        }

        $this->transition($transaction, StateMachineTransitionActions::ACTION_AUTHORIZE, $context);
    }

    /**
     * Transition the order transaction to `paid` state
     */
    public function refundTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        $targetStatuses = [
            OrderTransactionStates::STATE_REFUNDED,
        ];

        if ($this->isFinalOrTargetStatus($transaction->getStateMachineState(), $targetStatuses)) {
            return;
        }

        if (!$this->allowedTransition($transaction, StateMachineTransitionActions::ACTION_REFUND, $context)) {
            $this->payTransaction($transaction, $context);
        }

        $this->transition($transaction, StateMachineTransitionActions::ACTION_REFUND, $context);
    }

    /**
     * Transition the order transaction to `refunded_partially` state
     */
    public function partialRefundTransaction(OrderTransactionEntity $transaction, Context $context): void
    {
        $targetStatuses = [
            OrderTransactionStates::STATE_PARTIALLY_REFUNDED,
        ];

        if ($this->isFinalOrTargetStatus($transaction->getStateMachineState(), $targetStatuses)) {
            return;
        }

        if (!$this->allowedTransition($transaction, StateMachineTransitionActions::ACTION_REFUND_PARTIALLY, $context)) {
            $this->payTransaction($transaction, $context);
        }

        $this->transition($transaction, StateMachineTransitionActions::ACTION_REFUND_PARTIALLY, $context);
    }

    private function isFinalOrTargetStatus(StateMachineStateEntity $stateMachineState, array $targetStatus): bool
    {
        if ($this->isFinalStatus($stateMachineState)) {
            return true;
        }

        return $this->transitionService->inStates($stateMachineState, $targetStatus);
    }

    private function isFinalStatus(StateMachineStateEntity $stateMachineState): bool
    {
        return $stateMachineState->getTechnicalName() === OrderTransactionStates::STATE_REFUNDED;
    }

    private function allowedTransition(OrderTransactionEntity $transaction, string $transition, Context $context): bool
    {
        return $this->transitionService->allowedTransition($transaction->getId(), OrderTransactionDefinition::ENTITY_NAME, $transition, $context);
    }

    private function transition(OrderTransactionEntity $transaction, string $transitionName, Context $context): void
    {
        $this->transitionService->transition(
            OrderTransactionDefinition::ENTITY_NAME,
            $transaction->getId(),
            $transitionName,
            $context
        );
    }
}
