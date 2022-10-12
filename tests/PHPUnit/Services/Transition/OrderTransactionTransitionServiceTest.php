<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Services\Transition;

use Cko\Shopware6\Service\LoggerService;
use Cko\Shopware6\Service\Transition\OrderTransactionTransitionService;
use Cko\Shopware6\Service\Transition\TransitionService;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Cko\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class OrderTransactionTransitionServiceTest extends TestCase
{
    use OrderTrait;
    use ContextTrait;

    private OrderTransactionTransitionService $orderTransactionTransition;

    /**
     * @var TransitionService|MockObject
     */
    private $transitionService;

    private Context $context;

    public function setUp(): void
    {
        $this->context = $this->getContext($this);
        $this->transitionService = $this->createMock(TransitionService::class);
        $this->orderTransactionTransition = new OrderTransactionTransitionService(
            $this->createMock(LoggerService::class),
            $this->transitionService
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->orderTransactionTransition->getDecorated();
    }

    /**
     * @dataProvider openTransactionProvider
     */
    public function testOpenTransaction(
        int $expectedTransition,
        string $currentState,
        bool $hasStateMachineState,
        bool $isTargetStatus,
        bool $allowTransition
    ): void {
        $orderTransaction = $this->handleTwoTimeTransition($expectedTransition, $currentState, $hasStateMachineState, $isTargetStatus, $allowTransition, false);

        $this->orderTransactionTransition->openTransaction($orderTransaction, $this->context);
    }

    /**
     * @dataProvider processTransactionProvider
     */
    public function testProcessTransaction(
        int $expectedTransition,
        string $currentState,
        bool $hasStateMachineState,
        bool $isTargetStatus,
        bool $allowTransition,
        bool $isBridgeTargetStatus
    ): void {
        $orderTransaction = $this->handleTwoTimeTransition(
            $expectedTransition,
            $currentState,
            $hasStateMachineState,
            $isTargetStatus,
            $allowTransition,
            $isBridgeTargetStatus
        );

        $this->orderTransactionTransition->processTransaction($orderTransaction, $this->context);
    }

    /**
     * @dataProvider payTransactionProvider
     */
    public function testPayTransaction(
        int $expectedTransition,
        string $currentState,
        bool $hasStateMachineState,
        bool $isTargetStatus,
        bool $allowTransition,
        bool $isBridgeTargetStatus
    ): void {
        $orderTransaction = $this->handleTwoTimeTransition(
            $expectedTransition,
            $currentState,
            $hasStateMachineState,
            $isTargetStatus,
            $allowTransition,
            $isBridgeTargetStatus
        );

        $this->orderTransactionTransition->payTransaction($orderTransaction, $this->context);
    }

    /**
     * @dataProvider cancelTransactionProvider
     */
    public function testCancelTransaction(
        int $expectedTransition,
        string $currentState,
        bool $hasStateMachineState,
        bool $isTargetStatus,
        bool $allowTransition,
        bool $isBridgeTargetStatus
    ): void {
        $orderTransaction = $this->handleTwoTimeTransition(
            $expectedTransition,
            $currentState,
            $hasStateMachineState,
            $isTargetStatus,
            $allowTransition,
            $isBridgeTargetStatus
        );

        $this->orderTransactionTransition->cancelTransaction($orderTransaction, $this->context);
    }

    /**
     * @dataProvider failTransactionProvider
     */
    public function testFailTransaction(
        int $expectedTransition,
        string $currentState,
        bool $hasStateMachineState,
        bool $isTargetStatus,
        bool $allowTransition,
        bool $isBridgeTargetStatus
    ): void {
        $orderTransaction = $this->handleTwoTimeTransition(
            $expectedTransition,
            $currentState,
            $hasStateMachineState,
            $isTargetStatus,
            $allowTransition,
            $isBridgeTargetStatus
        );

        $this->orderTransactionTransition->failTransaction($orderTransaction, $this->context);
    }

    /**
     * @dataProvider authorizeTransactionProvider
     */
    public function testAuthorizeTransaction(
        int $expectedTransition,
        string $currentState,
        bool $hasStateMachineState,
        bool $isTargetStatus,
        bool $allowTransition,
        bool $isBridgeTargetStatus
    ): void {
        $orderTransaction = $this->handleTwoTimeTransition(
            $expectedTransition,
            $currentState,
            $hasStateMachineState,
            $isTargetStatus,
            $allowTransition,
            $isBridgeTargetStatus
        );

        $this->orderTransactionTransition->authorizeTransaction($orderTransaction, $this->context);
    }

    /**
     * @dataProvider refundTransactionProvider
     */
    public function testRefundTransaction(
        int $expectedTransition,
        string $currentState,
        bool $hasStateMachineState,
        bool $isTargetStatus,
        bool $allowTransition,
        bool $isBridgeTargetStatus
    ): void {
        $orderTransaction = $this->handleTwoTimeTransition(
            $expectedTransition,
            $currentState,
            $hasStateMachineState,
            $isTargetStatus,
            $allowTransition,
            $isBridgeTargetStatus
        );

        $this->orderTransactionTransition->refundTransaction($orderTransaction, $this->context);
    }

    /**
     * @dataProvider partialRefundTransactionProvider
     */
    public function testPartialRefundTransaction(
        int $expectedTransition,
        string $currentState,
        bool $hasStateMachineState,
        bool $isTargetStatus,
        bool $allowTransition,
        bool $isBridgeTargetStatus
    ): void {
        $orderTransaction = $this->handleTwoTimeTransition(
            $expectedTransition,
            $currentState,
            $hasStateMachineState,
            $isTargetStatus,
            $allowTransition,
            $isBridgeTargetStatus
        );

        $this->orderTransactionTransition->partialRefundTransaction($orderTransaction, $this->context);
    }

    public function openTransactionProvider(): array
    {
        return [
            'Test has not state machine state' => [
                0,
                OrderTransactionStates::STATE_REFUNDED,
                false,
                false,
                false,
            ],
            'Test is final state' => [
                0,
                OrderTransactionStates::STATE_REFUNDED,
                true,
                false,
                false,
            ],
            'Test does not final state but is in target status' => [
                0,
                OrderTransactionStates::STATE_OPEN,
                true,
                true,
                false,
            ],
            'Test allow transition' => [
                1,
                OrderTransactionStates::STATE_IN_PROGRESS,
                true,
                false,
                true,
            ],
            'Test does not allow transition' => [
                0,
                OrderTransactionStates::STATE_IN_PROGRESS,
                true,
                false,
                false,
            ],
        ];
    }

    public function processTransactionProvider(): array
    {
        return $this->getTwoTimeTransitionProvider(OrderTransactionStates::STATE_IN_PROGRESS);
    }

    public function payTransactionProvider(): array
    {
        return $this->getTwoTimeTransitionProvider(OrderTransactionStates::STATE_PAID);
    }

    public function cancelTransactionProvider(): array
    {
        return $this->getTwoTimeTransitionProvider(OrderTransactionStates::STATE_CANCELLED);
    }

    public function failTransactionProvider(): array
    {
        return $this->getTwoTimeTransitionProvider(OrderTransactionStates::STATE_FAILED);
    }

    public function authorizeTransactionProvider(): array
    {
        return $this->getTwoTimeTransitionProvider(OrderTransactionStates::STATE_AUTHORIZED);
    }

    public function refundTransactionProvider(): array
    {
        return $this->getTwoTimeTransitionProvider(OrderTransactionStates::STATE_REFUNDED, OrderTransactionStates::STATE_PAID);
    }

    public function partialRefundTransactionProvider(): array
    {
        return $this->getTwoTimeTransitionProvider(OrderTransactionStates::STATE_PARTIALLY_REFUNDED, OrderTransactionStates::STATE_PAID);
    }

    private function handleTwoTimeTransition(
        int $expectedTransition,
        string $currentState,
        bool $hasStateMachineState,
        bool $isTargetStatus,
        bool $allowTransition,
        bool $isBridgeTargetStatus
    ): OrderTransactionEntity {
        $orderTransaction = $this->getOrderTransaction();
        if ($hasStateMachineState) {
            $stateMachineState = new StateMachineStateEntity();
            $stateMachineState->setId('stateId');
            $stateMachineState->setName('foo');
            $stateMachineState->setTechnicalName($currentState);
            $orderTransaction->setStateMachineState($stateMachineState);

            $isFinal = $currentState === OrderTransactionStates::STATE_REFUNDED;
            $passThrowFinalOrTargetStatus = !$isFinal && !$isTargetStatus;

            $this->transitionService->expects(static::exactly($isFinal ? 0 : ($isBridgeTargetStatus ? 2 : 1)))
                ->method('inStates')
                ->willReturnOnConsecutiveCalls($isTargetStatus, $isBridgeTargetStatus);

            $this->transitionService->expects(static::exactly($passThrowFinalOrTargetStatus ? 1 : 0))
                ->method('allowedTransition')
                ->willReturn($allowTransition);

            $this->transitionService->expects(static::exactly($expectedTransition))
                ->method('transition');
        } else {
            static::expectException(Exception::class);
        }

        return $orderTransaction;
    }

    private function getTwoTimeTransitionProvider(string $inTargetStatus, string $bridgeStatus = OrderTransactionStates::STATE_OPEN): array
    {
        return [
            'Test has not state machine state' => [
                0,
                OrderTransactionStates::STATE_REFUNDED,
                false,
                false,
                false,
                false,
            ],
            'Test is final state' => [
                0,
                OrderTransactionStates::STATE_REFUNDED,
                true,
                false,
                false,
                false,
            ],
            'Test does not final state but is in target status' => [
                0,
                $inTargetStatus,
                true,
                true,
                false,
                false,
            ],
            'Test does not allow transition' => [
                1,
                OrderTransactionStates::STATE_OPEN,
                true,
                false,
                true,
                false,
            ],
            'Test does not allow transition so it will handle bridge transition but the status is already set to bridge status so it will be ignored' => [
                1,
                $bridgeStatus,
                true,
                false,
                false,
                true,
            ],
        ];
    }
}
