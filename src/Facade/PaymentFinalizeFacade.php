<?php declare(strict_types=1);

namespace Cko\Shopware6\Facade;

use Cko\Shopware6\Event\CheckoutFinalizeStatusEvent;
use Cko\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use Cko\Shopware6\Factory\SettingsFactory;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use Cko\Shopware6\Service\CustomerService;
use Cko\Shopware6\Service\Extractor\AbstractOrderExtractor;
use Cko\Shopware6\Service\Order\AbstractOrderService;
use Cko\Shopware6\Service\Order\AbstractOrderTransactionService;
use Cko\Shopware6\Service\Order\OrderService;
use Cko\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Cko\Shopware6\Struct\CheckoutApi\Resources\PaymentSource;
use Cko\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentFinalizeFacade
{
    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    private CheckoutPaymentService $checkoutPaymentService;

    private SettingsFactory $settingsFactory;

    private CustomerService $customerService;

    private AbstractOrderExtractor $orderExtractor;

    private AbstractOrderService $orderService;

    private AbstractOrderTransactionService $orderTransactionService;

    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        CheckoutPaymentService $checkoutPaymentService,
        SettingsFactory $settingsFactory,
        CustomerService $customerService,
        AbstractOrderExtractor $orderExtractor,
        AbstractOrderService $orderService,
        AbstractOrderTransactionService $orderTransactionService
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->checkoutPaymentService = $checkoutPaymentService;
        $this->settingsFactory = $settingsFactory;
        $this->customerService = $customerService;
        $this->orderExtractor = $orderExtractor;
        $this->orderService = $orderService;
        $this->orderTransactionService = $orderTransactionService;
    }

    /**
     * @throws Exception
     */
    public function finalize(
        PaymentHandler $paymentHandler,
        AsyncPaymentTransactionStruct $transaction,
        SalesChannelContext $salesChannelContext
    ): void {
        $order = $transaction->getOrder();
        $orderCustomer = $this->orderExtractor->extractCustomer($order);
        $customerId = $orderCustomer->getCustomerId();
        if (empty($customerId)) {
            throw new Exception('Customer ID not found');
        }

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

        if ($paymentStatus === CheckoutPaymentService::STATUS_CANCELED) {
            $message = 'Checkout.com payment is canceled';
            $this->logger->error($message, [
                'orderNumber' => $order->getOrderNumber(),
                'orderTransactionId' => $orderTransaction->getId(),
            ]);

            // Order payment status will be changed to "Canceled" after throw this exception
            throw new CustomerCanceledAsyncPaymentException($orderTransaction->getId(), $message);
        }

        if ($paymentStatus === CheckoutPaymentService::STATUS_DECLINED) {
            $message = 'Checkout.com payment is declined';
            $this->logger->error($message, [
                'orderNumber' => $order->getOrderNumber(),
                'orderTransactionId' => $orderTransaction->getId(),
            ]);

            // Order payment status will be changed to "Failed" after throw this exception
            throw new AsyncPaymentFinalizeException($orderTransaction->getId(), $message);
        }

        $canManualCapture = $paymentHandler->canManualCapture($salesChannelContext);
        $checkoutOrderCustomFields->setManualCapture($canManualCapture);

        if ($payment->getStatus() === CheckoutPaymentService::STATUS_AUTHORIZED && !$canManualCapture) {
            $actionId = $paymentHandler->capturePayment($checkoutPaymentId, $order);
            $checkoutOrderCustomFields->setLastCheckoutActionId($actionId);
            $paymentStatus = CheckoutPaymentService::STATUS_CAPTURED;
        }

        $this->eventDispatcher->dispatch(new CheckoutFinalizeStatusEvent($order, $payment, $paymentStatus));

        $this->orderService->updateCheckoutCustomFields($order, $checkoutOrderCustomFields, $salesChannelContext->getContext());

        $settings = $this->settingsFactory->getSettings($salesChannelContext->getSalesChannelId());

        // Update the order transaction of Shopware depending on checkout.com payment status
        $this->orderTransactionService->processTransition($orderTransaction, $paymentStatus, $salesChannelContext->getContext());

        // Update the order status of Shopware depending on checkout.com payment status
        $this->orderService->processTransition($order, $settings, $paymentStatus, $salesChannelContext->getContext());

        $this->saveCustomerSource($customerId, $checkoutOrderCustomFields, $payment, $salesChannelContext);
    }

    private function saveCustomerSource(
        string $customerId,
        OrderCustomFieldsStruct $checkoutOrderCustomFields,
        Payment $payment,
        SalesChannelContext $salesChannelContext
    ): void {
        if (!$checkoutOrderCustomFields->isShouldSaveSource()) {
            return;
        }

        $source = $payment->getSource();
        if (empty($source)) {
            return;
        }

        $paymentSource = (new PaymentSource())->assign($source);

        $this->customerService->saveCustomerSource($customerId, $paymentSource, $salesChannelContext);
    }
}
