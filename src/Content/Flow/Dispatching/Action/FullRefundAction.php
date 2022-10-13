<?php declare(strict_types=1);

namespace Cko\Shopware6\Content\Flow\Dispatching\Action;

use Cko\Shopware6\Facade\PaymentRefundFacade;
use Cko\Shopware6\Service\Builder\RefundBuilder;
use Cko\Shopware6\Service\Order\AbstractOrderService;
use Cko\Shopware6\Service\Order\OrderService;
use Cko\Shopware6\Struct\Request\Refund\RefundItemRequestCollection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Flow\Dispatching\Action\FlowAction;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\FlowEvent;
use Shopware\Core\Framework\Event\OrderAware;

class FullRefundAction extends FlowAction
{
    private LoggerInterface $logger;

    private AbstractOrderService $orderService;

    private PaymentRefundFacade $refundFacade;

    private RefundBuilder $refundBuilder;

    public function __construct(
        LoggerInterface $logger,
        AbstractOrderService $orderService,
        PaymentRefundFacade $refundFacade,
        RefundBuilder $refundBuilder
    ) {
        $this->logger = $logger;
        $this->orderService = $orderService;
        $this->refundFacade = $refundFacade;
        $this->refundBuilder = $refundBuilder;
    }

    public static function getSubscribedEvents(): array
    {
        return [self::getName() => 'handle'];
    }

    public static function getName(): string
    {
        return 'action.checkout_com.full_refund';
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

        $this->logger->info(sprintf('Action starting to full refund with order ID: %s', $baseEvent->getOrderId()));
        $this->handleFullRefund($baseEvent->getOrderId(), $baseEvent->getContext());
    }

    private function handleFullRefund(string $orderId, Context $context): void
    {
        $order = $this->orderService->getOrder($context, $orderId, ['lineItems']);
        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $orderCustomFields->getCheckoutPaymentId();

        // Skip if the order don't have checkout payment ID
        if (empty($checkoutPaymentId)) {
            $this->logger->error(sprintf('No checkout.com payment ID found for order number: %s', $order->getOrderNumber()));

            return;
        }

        $refundRequest = $this->refundBuilder->buildRefundRequestForFullRefund($order);
        $refundItemsRequest = $refundRequest->getItems();
        if (!$refundItemsRequest instanceof RefundItemRequestCollection) {
            $this->logger->error('The $refundItemsRequest must be instance of RefundItemRequestCollection');

            return;
        }

        // Skip if the refund request don't have any items
        if ($refundItemsRequest->count() === 0) {
            $this->logger->warning('The refund request do not have any items to refund');

            return;
        }

        $this->refundFacade->refundPayment($refundRequest, $context);
    }
}
