<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Content\Flow\Dispatching\Action;

use Cko\Shopware6\Content\Flow\Dispatching\Action\FullRefundAction;
use Cko\Shopware6\Facade\PaymentRefundFacade;
use Cko\Shopware6\Service\Builder\RefundBuilder;
use Cko\Shopware6\Service\Order\AbstractOrderService;
use Cko\Shopware6\Struct\Request\Refund\OrderRefundRequest;
use Cko\Shopware6\Struct\Request\Refund\RefundItemRequestCollection;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Cko\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Event\FlowEvent;
use Shopware\Core\Framework\Event\FlowEventAware;
use Shopware\Core\Framework\Event\OrderAware;

class FullRefundActionTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    private FullRefundAction $fullRefundAction;

    /**
     * @var AbstractOrderService|MockObject
     */
    private $orderService;

    /**
     * @var PaymentRefundFacade|MockObject
     */
    private $refundFacade;

    /**
     * @var RefundBuilder|MockObject
     */
    private $refundBuilder;

    private $context;

    protected function setUp(): void
    {
        $this->context = $this->getContext($this);
        $this->orderService = $this->createMock(AbstractOrderService::class);
        $this->refundFacade = $this->createMock(PaymentRefundFacade::class);
        $this->refundBuilder = $this->createMock(RefundBuilder::class);

        $this->fullRefundAction = new FullRefundAction(
            $this->createMock(LoggerInterface::class),
            $this->orderService,
            $this->refundFacade,
            $this->refundBuilder
        );
    }

    public function testGetSubscribedEventsCorrect(): void
    {
        static::assertArrayHasKey(FullRefundAction::getName(), FullRefundAction::getSubscribedEvents());
    }

    public function testGetNameCorrect(): void
    {
        static::assertSame('action.checkout_com.full_refund', FullRefundAction::getName());
    }

    public function testRequirementsCorrect(): void
    {
        static::assertContains(OrderAware::class, $this->fullRefundAction->requirements());
    }

    public function testHandleOfInvalidOrderAware(): void
    {
        $baseEvent = $this->createMock(FlowEventAware::class);

        $event = $this->createConfiguredMock(FlowEvent::class, [
            'getEvent' => $baseEvent,
        ]);

        $this->orderService->expects(static::never())
            ->method('getOrder');

        $this->fullRefundAction->handle($event);
    }

    public function testHandleOfEmptyCheckoutPaymentId(): void
    {
        $baseEvent = $this->createConfiguredMock(OrderAware::class, [
            'getOrderId' => 'foo',
            'getContext' => $this->context,
        ]);

        $event = $this->createConfiguredMock(FlowEvent::class, [
            'getEvent' => $baseEvent,
        ]);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->getOrder());

        $this->refundBuilder->expects(static::never())
            ->method('buildRefundRequestForFullRefund');

        $this->fullRefundAction->handle($event);
    }

    public function testHandleOfNullRefundItemRequestCollection(): void
    {
        $baseEvent = $this->createConfiguredMock(OrderAware::class, [
            'getOrderId' => 'foo',
            'getContext' => $this->context,
        ]);

        $event = $this->createConfiguredMock(FlowEvent::class, [
            'getEvent' => $baseEvent,
        ]);

        $refundRequest = $this->createConfiguredMock(OrderRefundRequest::class, [
            'getItems' => null,
        ]);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->getOrder('foo'));

        $this->refundBuilder->expects(static::once())
            ->method('buildRefundRequestForFullRefund')
            ->willReturn($refundRequest);

        $this->refundFacade->expects(static::never())
            ->method('refundPayment');

        $this->fullRefundAction->handle($event);
    }

    public function testHandleOfEmptyRefundItemsRequest(): void
    {
        $baseEvent = $this->createConfiguredMock(OrderAware::class, [
            'getOrderId' => 'foo',
            'getContext' => $this->context,
        ]);

        $event = $this->createConfiguredMock(FlowEvent::class, [
            'getEvent' => $baseEvent,
        ]);

        $refundItemsRequest = $this->createMock(RefundItemRequestCollection::class);

        $refundRequest = $this->createConfiguredMock(OrderRefundRequest::class, [
            'getItems' => $refundItemsRequest,
        ]);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->getOrder('foo'));

        $this->refundBuilder->expects(static::once())
            ->method('buildRefundRequestForFullRefund')
            ->willReturn($refundRequest);

        $this->refundFacade->expects(static::never())
            ->method('refundPayment');

        $this->fullRefundAction->handle($event);
    }

    public function testHandleSuccessful(): void
    {
        $baseEvent = $this->createConfiguredMock(OrderAware::class, [
            'getOrderId' => 'foo',
            'getContext' => $this->context,
        ]);

        $event = $this->createConfiguredMock(FlowEvent::class, [
            'getEvent' => $baseEvent,
        ]);

        $refundItemsRequest = $this->createConfiguredMock(RefundItemRequestCollection::class, [
            'count' => 2,
        ]);

        $refundRequest = $this->createConfiguredMock(OrderRefundRequest::class, [
            'getItems' => $refundItemsRequest,
        ]);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->getOrder('foo'));

        $this->refundBuilder->expects(static::once())
            ->method('buildRefundRequestForFullRefund')
            ->willReturn($refundRequest);

        $this->refundFacade->expects(static::once())
            ->method('refundPayment');

        $this->fullRefundAction->handle($event);
    }
}
