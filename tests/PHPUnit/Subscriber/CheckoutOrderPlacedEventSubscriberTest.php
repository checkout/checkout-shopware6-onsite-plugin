<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Subscriber;

use Cko\Shopware6\Service\Order\OrderService;
use Cko\Shopware6\Subscriber\CheckoutOrderPlacedEventSubscriber;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Cko\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CheckoutOrderPlacedEventSubscriberTest extends TestCase
{
    use OrderTrait;
    use ContextTrait;

    private CheckoutOrderPlacedEventSubscriber $subscriber;

    /**
     * @var MockObject|OrderService
     */
    private $orderService;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->orderService = $this->createMock(OrderService::class);
        $this->subscriber = new CheckoutOrderPlacedEventSubscriber($this->orderService);
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey(CheckoutOrderPlacedEvent::class, CheckoutOrderPlacedEventSubscriber::getSubscribedEvents());
    }

    public function testOnCheckoutOrderPlaced(): void
    {
        $event = new CheckoutOrderPlacedEvent(
            $this->salesChannelContext->getContext(),
            $this->getOrder(),
            $this->salesChannelContext->getSalesChannelId()
        );

        $this->orderService->expects(static::once())
            ->method('setRequestLastOrderId');
        $this->subscriber->onCheckoutOrderPlaced($event);
    }
}
