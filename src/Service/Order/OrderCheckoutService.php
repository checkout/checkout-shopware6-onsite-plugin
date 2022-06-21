<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Throwable;

class OrderCheckoutService extends AbstractOrderCheckoutService
{
    private LoggerService $logger;

    private AbstractOrderService $orderService;

    private CheckoutPaymentService $checkoutPaymentService;

    public function __construct(LoggerService $loggerService, AbstractOrderService $orderService, CheckoutPaymentService $checkoutPaymentService)
    {
        $this->logger = $loggerService;
        $this->orderService = $orderService;
        $this->checkoutPaymentService = $checkoutPaymentService;
    }

    public function getDecorated(): AbstractOrderCheckoutService
    {
        throw new DecorationPatternException(self::class);
    }

    public function getCheckoutPayment(string $orderId, Context $context): Payment
    {
        $order = $this->orderService->getOrder($context, $orderId);

        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutPaymentId = $orderCustomFields->getCheckoutPaymentId();
        if (empty($checkoutPaymentId)) {
            $this->logger->error(sprintf('Error while getting checkoutPaymentId from custom fields of order ID: %s', $orderId));

            throw new CheckoutPaymentIdNotFoundException($order);
        }

        try {
            $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $order->getSalesChannelId());
            $actions = $this->checkoutPaymentService->getPaymentActions($checkoutPaymentId, $order->getSalesChannelId());

            return $payment->assign([
                'actions' => $actions,
            ]);
        } catch (Throwable $ex) {
            $message = sprintf('Error while getting payment details for order ID: %s, checkoutPaymentId: %s', $orderId, $checkoutPaymentId);
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }
    }
}
