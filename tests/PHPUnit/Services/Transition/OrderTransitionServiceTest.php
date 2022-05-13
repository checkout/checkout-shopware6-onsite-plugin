<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Transition;

use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\Transition\OrderTransitionService;
use CheckoutCom\Shopware6\Service\Transition\TransitionService;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class OrderTransitionServiceTest extends TestCase
{
    use OrderTrait;
    use ContextTrait;

    private OrderTransitionService $orderTransitionService;

    /**
     * @var TransitionService|MockObject
     */
    private $transitionService;

    /**
     * @var LoggerService|MockObject
     */
    private $logger;

    private Context $context;

    public function setUp(): void
    {
        $this->context = $this->getContext($this);
        $this->transitionService = $this->createMock(TransitionService::class);
        $this->logger = $this->createMock(LoggerService::class);
        $this->orderTransitionService = new OrderTransitionService($this->logger, $this->transitionService);
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->orderTransitionService->getDecorated();
    }

    /**
     * @dataProvider getSetTransitionStateProvider
     */
    public function testSetTransitionState(string $targetState): void
    {
        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setId('stateId');

        $order = $this->getOrder();
        $order->setStateMachineState($stateMachineState);

        if ($targetState === SettingStruct::ORDER_STATE_SKIP) {
            $this->transitionService->expects(static::never())->method('inStates');

            $this->logger->expects(static::never())->method('critical');
        } else {
            $this->transitionService->expects(static::atLeastOnce())
                ->method('inStates')
                ->with($stateMachineState, [$targetState])
                ->willReturn(true);
        }

        $this->orderTransitionService->setTransitionState($order, $targetState, $this->context);
    }

    public function testSetTransitionStateWithNotExistsStateMustThrowException(): void
    {
        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setId('stateId');

        $order = $this->getOrder();
        $order->setStateMachineState($stateMachineState);

        $this->transitionService->expects(static::never())->method('inStates');
        static::expectException(Exception::class);

        $this->orderTransitionService->setTransitionState($order, 'any not exists state', $this->context);
    }

    /**
     * @dataProvider openOrderProvider
     */
    public function testOpenOrder(int $expected, bool $isSameState, bool $isAllowedTransition): void
    {
        $order = $this->handleExpectTransitionOrder($expected, $isSameState, $isAllowedTransition, false);

        $this->orderTransitionService->openOrder($order, $this->context);
    }

    /**
     * @dataProvider progressOrderProvider
     */
    public function testProgressOrder(int $expected, bool $isSameState, bool $isAllowedTransition): void
    {
        $order = $this->handleExpectTransitionOrder($expected, $isSameState, $isAllowedTransition, false);

        $this->orderTransitionService->progressOrder($order, $this->context);
    }

    /**
     * @dataProvider completeOrderProvider
     */
    public function testCompleteOrder(int $expected, bool $sameCompleteState, bool $allowedCompleteTransition, bool $sameProgressState): void
    {
        $order = $this->handleExpectTransitionOrder($expected, $sameCompleteState, $allowedCompleteTransition, $sameProgressState);

        $this->orderTransitionService->completeOrder($order, $this->context);
    }

    /**
     * @dataProvider cancelOrderProvider
     */
    public function testCancelOrder(int $expected, bool $isSameState, bool $isAllowedTransition): void
    {
        $order = $this->handleExpectTransitionOrder($expected, $isSameState, $isAllowedTransition, false);

        $this->orderTransitionService->cancelOrder($order, $this->context);
    }

    public function getSetTransitionStateProvider(): array
    {
        return [
            'Test skip transition' => [
                SettingStruct::ORDER_STATE_SKIP,
            ],
            'Test for case open transition' => [
                OrderStates::STATE_OPEN,
            ],
            'Test for case in process transition' => [
                OrderStates::STATE_IN_PROGRESS,
            ],
            'Test for case completed transition' => [
                OrderStates::STATE_COMPLETED,
            ],
            'Test for case cancelled transition' => [
                OrderStates::STATE_CANCELLED,
            ],
        ];
    }

    public function openOrderProvider(): array
    {
        return [
            'Test same state' => [
                0,
                true,
                false,
            ],
            'Test allowed transition' => [
                1,
                false,
                true,
            ],
            'Test not allowed transition' => [
                2,
                false,
                false,
            ],
        ];
    }

    public function progressOrderProvider(): array
    {
        return [
            'Test same state' => [
                0,
                true,
                false,
            ],
            'Test allowed transition' => [
                1,
                false,
                true,
            ],
            'Test not allowed transition' => [
                2,
                false,
                false,
            ],
        ];
    }

    public function completeOrderProvider(): array
    {
        return [
            'Test same completed state' => [
                0,
                true,
                false,
                false,
                false,
            ],
            'Test allowed complete transition' => [
                1,
                false,
                true,
                false,
                false,
            ],
            'Test not allowed complete transition but same in process state' => [
                1,
                false,
                false,
                true,
                false,
            ],
        ];
    }

    public function cancelOrderProvider(): array
    {
        return [
            'Test same state' => [
                0,
                true,
                false,
            ],
            'Test allowed transition' => [
                1,
                false,
                true,
            ],
            'Test not allowed transition' => [
                2,
                false,
                false,
            ],
        ];
    }

    private function handleExpectTransitionOrder(
        int $expected,
        bool $isSameState,
        bool $isAllowedTransition,
        bool $isBridgeSameState
    ): OrderEntity {
        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setId('stateId');

        $order = $this->getOrder();
        $order->setStateMachineState($stateMachineState);

        $this->transitionService->expects(static::exactly($isBridgeSameState ? 2 : 1))
            ->method('inStates')
            ->willReturnOnConsecutiveCalls($isSameState, $isBridgeSameState);

        $this->transitionService->expects(static::exactly($isSameState ? 0 : 1))
            ->method('allowedTransition')
            ->willReturn($isAllowedTransition);

        $this->transitionService->expects(static::exactly($expected))
            ->method('transition');

        return $order;
    }
}
