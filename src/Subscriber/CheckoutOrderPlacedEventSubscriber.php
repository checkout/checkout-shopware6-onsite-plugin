<?php declare(strict_types=1);

namespace Cko\Shopware6\Subscriber;

use Cko\Shopware6\Service\Order\AbstractOrderService;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutOrderPlacedEventSubscriber implements EventSubscriberInterface
{
    private AbstractOrderService $orderService;

    public function __construct(AbstractOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onCheckoutOrderPlaced',
        ];
    }

    /**
     * Save the last order ID to the Order Service Instance to read it from the response subscriber
     * This CheckoutOrderPlacedEvent event is triggered after the order is placed
     *
     * @see \Cko\Shopware6\Subscriber\BeforeSendResponseEventSubscriber::onBeforeSendResponse
     */
    public function onCheckoutOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $this->orderService->setRequestLastOrderId($event->getOrder()->getId());
    }
}
