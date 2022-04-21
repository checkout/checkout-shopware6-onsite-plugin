<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Facade;

use CheckoutCom\Shopware6\Event\CheckoutFinalizeStatusEvent;
use CheckoutCom\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentFinalizeFacade
{
    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    private CheckoutPaymentService $checkoutPaymentService;

    private SettingsFactory $settingsFactory;

    private OrderService $orderService;

    private OrderTransactionService $orderTransactionService;

    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        CheckoutPaymentService $checkoutPaymentService,
        SettingsFactory $settingsFactory,
        OrderService $orderService,
        OrderTransactionService $orderTransactionService
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->checkoutPaymentService = $checkoutPaymentService;
        $this->settingsFactory = $settingsFactory;
        $this->orderService = $orderService;
        $this->orderTransactionService = $orderTransactionService;
    }

    /**
     * @throws Exception
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext): void
    {
        $order = $transaction->getOrder();
        $orderTransaction = $transaction->getOrderTransaction();
        $checkoutOrderCustomFields = OrderService::getCheckoutOrderCustomFields($order);

        $checkoutPaymentId = $checkoutOrderCustomFields->getCheckoutPaymentId();

        if (empty($checkoutPaymentId)) {
            $this->logger->error('No checkout.com payment ID found for order', [
                'orderNumber' => $order->getOrderNumber(),
                'orderTransactionId' => $orderTransaction->getId(),
            ]);

            throw new CheckoutPaymentIdNotFoundException($order);
        }

        // Get the payment from the checkout.com API
        $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $salesChannelContext->getSalesChannelId());
        $paymentStatus = $payment->getStatus();

        if ($paymentStatus === CheckoutPaymentService::STATUS_AUTHORIZED) {
            // We capture the payment from the Checkout.com API, the CheckoutApiException will be thrown if capturing is failed
            $this->checkoutPaymentService->capturePayment($checkoutPaymentId, $salesChannelContext->getSalesChannelId());

            // If we successfully capture the payment, we can mark the status as captured
            $paymentStatus = CheckoutPaymentService::STATUS_CAPTURED;
        }

        $this->eventDispatcher->dispatch(new CheckoutFinalizeStatusEvent($order, $payment, $paymentStatus));

        $settings = $this->settingsFactory->getSettings($salesChannelContext->getSalesChannelId());

        // Update the order transaction of Shopware depending on checkout.com payment status
        $this->orderTransactionService->processTransition($orderTransaction, $paymentStatus, $salesChannelContext->getContext());

        // Update the order status of Shopware depending on checkout.com payment status
        $this->orderService->processTransition($order, $settings, $paymentStatus, $salesChannelContext->getContext());
    }
}
