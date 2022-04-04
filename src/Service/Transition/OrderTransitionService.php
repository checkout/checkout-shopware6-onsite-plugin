<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Transition;

use CheckoutCom\Shopware6\Struct\SettingStruct;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

class OrderTransitionService extends AbstractOrderTransitionService
{
    private LoggerInterface $logger;

    private TransitionService $transitionService;

    public function __construct(LoggerInterface $logger, TransitionService $transitionService)
    {
        $this->logger = $logger;
        $this->transitionService = $transitionService;
    }

    public function getDecorated(): AbstractOrderTransitionService
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @throws Exception
     */
    public function setTransitionState(OrderEntity $order, string $transitionState, Context $context): void
    {
        // If order state is skip we don't set a new order state
        if ($transitionState === SettingStruct::ORDER_STATE_SKIP) {
            return;
        }

        switch ($transitionState) {
            case OrderStates::STATE_OPEN:
                $this->openOrder($order, $context);

                break;
            case OrderStates::STATE_IN_PROGRESS:
                $this->progressOrder($order, $context);

                break;
            case OrderStates::STATE_COMPLETED:
                $this->completeOrder($order, $context);

                break;
            case OrderStates::STATE_CANCELLED:
                $this->cancelOrder($order, $context);

                break;
            default:
                $this->logger->critical('Unknown Order states', [
                    'orderId' => $order->getId(),
                    'status' => $transitionState,
                ]);

                throw new Exception(sprintf('Set order state not possible for order status: %s', $transitionState));
        }
    }

    /**
     * Transition the order to `open` state
     */
    public function openOrder(OrderEntity $order, Context $context): void
    {
        if ($this->transitionService->inStates($order->getStateMachineState(), [OrderStates::STATE_OPEN])) {
            return;
        }

        if (!$this->allowedTransition($order, StateMachineTransitionActions::ACTION_REOPEN, $context)) {
            $this->transition($order, StateMachineTransitionActions::ACTION_COMPLETE, $context);
        }

        $this->transition($order, StateMachineTransitionActions::ACTION_REOPEN, $context);
    }

    /**
     * Transition the order to `in_progress` state
     */
    public function progressOrder(OrderEntity $order, Context $context): void
    {
        if ($this->transitionService->inStates($order->getStateMachineState(), [OrderStates::STATE_IN_PROGRESS])) {
            return;
        }

        if (!$this->allowedTransition($order, StateMachineTransitionActions::ACTION_PROCESS, $context)) {
            $this->transition($order, StateMachineTransitionActions::ACTION_REOPEN, $context);
        }

        $this->transition($order, StateMachineTransitionActions::ACTION_PROCESS, $context);
    }

    /**
     * Transition the order to `completed` state
     */
    public function completeOrder(OrderEntity $order, Context $context): void
    {
        if ($this->transitionService->inStates($order->getStateMachineState(), [OrderStates::STATE_COMPLETED])) {
            return;
        }

        if (!$this->allowedTransition($order, StateMachineTransitionActions::ACTION_COMPLETE, $context)) {
            $this->progressOrder($order, $context);
        }

        $this->transition($order, StateMachineTransitionActions::ACTION_COMPLETE, $context);
    }

    /**
     * Transition the order to `cancelled` state
     */
    public function cancelOrder(OrderEntity $order, Context $context): void
    {
        if ($this->transitionService->inStates($order->getStateMachineState(), [OrderStates::STATE_CANCELLED])) {
            return;
        }

        if (!$this->allowedTransition($order, StateMachineTransitionActions::ACTION_CANCEL, $context)) {
            $this->transition($order, StateMachineTransitionActions::ACTION_REOPEN, $context);
        }

        $this->transition($order, StateMachineTransitionActions::ACTION_CANCEL, $context);
    }

    private function allowedTransition(OrderEntity $order, string $transition, Context $context): bool
    {
        return $this->transitionService->allowedTransition($order->getId(), OrderDefinition::ENTITY_NAME, $transition, $context);
    }

    private function transition(OrderEntity $order, string $transitionName, Context $context): void
    {
        $this->transitionService->transition(
            OrderDefinition::ENTITY_NAME,
            $order->getId(),
            $transitionName,
            $context
        );
    }
}
