<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Transition;

use Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransitionService
{
    private StateMachineRegistry $stateMachineRegistry;

    public function __construct(StateMachineRegistry $stateMachineRegistry)
    {
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    /**
     * @throws Exception
     */
    public function inStates(?StateMachineStateEntity $stateMachineState, array $targetStates): bool
    {
        if (!$stateMachineState instanceof StateMachineStateEntity) {
            throw new Exception(sprintf(
                'State machine state is not an instance of StateMachineStateEntity, method: %s',
                __METHOD__
            ));
        }

        return \in_array($stateMachineState->getTechnicalName(), $targetStates, true);
    }

    /**
     * Check if the transition is allowed for the given transition action.
     */
    public function allowedTransition(string $entityId, string $definitionName, string $transitionAction, Context $context): bool
    {
        // We get available transition actions for the given entity
        $availableTransitionActions = $this->getAvailableTransitionActions($definitionName, $entityId, $context);

        // Only allow the transition action if it is available
        return \in_array($transitionAction, $availableTransitionActions, true);
    }

    public function getAvailableTransitionActions(string $definitionName, string $entityId, Context $context): array
    {
        /** @var array<StateMachineTransitionEntity> $availableTransitions */
        $availableTransitions = $this->stateMachineRegistry->getAvailableTransitions(
            $definitionName,
            $entityId,
            'stateId',
            $context
        );

        // We only want the technical names of the transitions
        return array_map(function (StateMachineTransitionEntity $transition) {
            return $transition->getActionName();
        }, $availableTransitions);
    }

    public function transition(string $definitionName, string $entityId, string $transitionName, Context $context): void
    {
        $this->stateMachineRegistry->transition(
            new Transition(
                $definitionName,
                $entityId,
                $transitionName,
                'stateId'
            ),
            $context
        );
    }
}
