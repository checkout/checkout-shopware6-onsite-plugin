<?php declare(strict_types=1);

namespace Cko\Shopware6\Content\Flow\Dispatching\Action;

use Cko\Shopware6\Service\Order\AbstractOrderCheckoutService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Flow\Dispatching\Action\FlowAction;
use Shopware\Core\Framework\Event\FlowEvent;
use Shopware\Core\Framework\Event\OrderAware;

class CapturePaymentAction extends FlowAction
{
    private LoggerInterface $logger;

    private AbstractOrderCheckoutService $orderCheckoutService;

    public function __construct(
        LoggerInterface $logger,
        AbstractOrderCheckoutService $orderCheckoutService
    ) {
        $this->logger = $logger;
        $this->orderCheckoutService = $orderCheckoutService;
    }

    public static function getSubscribedEvents(): array
    {
        return [self::getName() => 'handle'];
    }

    public static function getName(): string
    {
        return 'action.checkout_com.capture_payment';
    }

    public function requirements(): array
    {
        return [OrderAware::class];
    }

    public function handle(FlowEvent $event): void
    {
        $baseEvent = $event->getEvent();
        if (!$baseEvent instanceof OrderAware) {
            return;
        }

        $this->logger->info(sprintf('Action starting to capture payment with order ID: %s', $baseEvent->getOrderId()));
        $this->orderCheckoutService->capturePayment($baseEvent->getOrderId(), $baseEvent->getContext());
    }
}
