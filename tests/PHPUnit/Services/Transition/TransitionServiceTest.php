<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Transition;

use CheckoutCom\Shopware6\Service\Transition\TransitionService;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class TransitionServiceTest extends TestCase
{
    use OrderTrait;
    use ContextTrait;

    /**
     * @var MockObject|StateMachineRegistry
     */
    private $stateMachineRegistry;

    private SalesChannelContext $salesChannelContext;

    private TransitionService $transitionService;

    public function setUp(): void
    {
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->stateMachineRegistry = $this->createMock(StateMachineRegistry::class);
        $this->transitionService = new TransitionService($this->stateMachineRegistry);
    }

    /**
     * @dataProvider getInStateProvider
     */
    public function testInStates(?string $stateMachineStateId, string $technicalName, array $targetStates, $expected): void
    {
        $stateMachine = null;
        if ($stateMachineStateId) {
            $stateMachine = new StateMachineStateEntity();
            $stateMachine->setId($stateMachineStateId);
            $stateMachine->setTechnicalName($technicalName);
        } else {
            static::expectException(Exception::class);
        }

        $result = $this->transitionService->inStates($stateMachine, $targetStates);
        static::assertSame($expected, $result);
    }

    /**
     * @dataProvider getAllowedTransitionProvider
     */
    public function testAllowedTransition(array $transitionActions, string $targetTransition, bool $expected): void
    {
        $definitionName = OrderDefinition::ENTITY_NAME;

        $result = [];
        foreach ($transitionActions as $transitionAction) {
            $transition = new StateMachineTransitionEntity();
            $transition->setId($transitionAction . 'id');
            $transition->setActionName($transitionAction);

            $result[] = $transition;
        }

        $order = $this->getOrder();
        $orderId = $order->getId();

        $this->stateMachineRegistry->expects(static::once())
            ->method('getAvailableTransitions')
            ->with($definitionName, $orderId, 'stateId', $this->salesChannelContext->getContext())
            ->willReturn($result);

        $allowedTransition = $this->transitionService->allowedTransition($orderId, $definitionName, $targetTransition, $this->salesChannelContext->getContext());

        static::assertSame($expected, $allowedTransition);
    }

    public function testTransition(): void
    {
        $definitionName = OrderDefinition::ENTITY_NAME;
        $transitionName = 'foo';

        $order = $this->getOrder();
        $orderId = $order->getId();

        $transition = new Transition(
            $definitionName,
            $orderId,
            $transitionName,
            'stateId'
        );

        $this->stateMachineRegistry->expects(static::once())
            ->method('transition')
            ->with($transition, $this->salesChannelContext->getContext());

        $this->transitionService->transition($definitionName, $orderId, $transitionName, $this->salesChannelContext->getContext());
    }

    public function getInStateProvider(): array
    {
        return [
            'Test State machine state is not an instance of StateMachineStateEntity' => [
                null,
                'technicalName' => 'state_1',
                'targetStates' => ['state_1', 'state_2'],
                'expected' => false,
            ],
            'Test in states' => [
                '123',
                'technicalName' => 'state_1',
                'targetStates' => ['state_1', 'state_2'],
                'expected' => true,
            ],
            'test not instates' => [
                '123',
                'technicalName' => 'state_1',
                'targetStates' => ['state_3', 'state_2'],
                'expected' => false,
            ],
        ];
    }

    public function getAllowedTransitionProvider(): array
    {
        return [
            'Test not allow transition' => [
                'transitionActions' => ['transition_1', 'transition_2'],
                'targetTransition' => 'transition_3',
                'expected' => false,
            ],
            'Test allow transition' => [
                'transitionActions' => ['transition_1', 'transition_2'],
                'targetTransition' => 'transition_1',
                'expected' => true,
            ],
        ];
    }
}
