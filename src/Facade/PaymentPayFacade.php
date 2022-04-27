<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Facade;

use Checkout\CheckoutApiException;
use Checkout\Payments\PaymentRequest;
use CheckoutCom\Shopware6\Event\CheckoutRequestPaymentEvent;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Extractor\OrderExtractor;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use CheckoutCom\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use CheckoutCom\Shopware6\Struct\PaymentHandler\HandlerPrepareProcessStruct;
use CheckoutCom\Shopware6\Struct\SettingStruct;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PaymentPayFacade
{
    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    private SettingsFactory $settingsFactory;

    private CheckoutPaymentService $checkoutPaymentService;

    private OrderExtractor $orderExtractor;

    private OrderService $orderService;

    private OrderTransactionService $orderTransactionService;

    private RouterInterface $router;

    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        SettingsFactory $settingsFactory,
        CheckoutPaymentService $checkoutPaymentService,
        OrderExtractor $orderExtractor,
        OrderService $orderService,
        OrderTransactionService $orderTransactionService,
        RouterInterface $router
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->settingsFactory = $settingsFactory;
        $this->checkoutPaymentService = $checkoutPaymentService;
        $this->orderExtractor = $orderExtractor;
        $this->orderService = $orderService;
        $this->orderTransactionService = $orderTransactionService;
        $this->router = $router;
    }

    /**
     * Prepare the payment process for payment handler
     *
     * @throws Exception
     * @throws ConstraintViolationException
     * @throws AsyncPaymentProcessException
     */
    public function pay(
        PaymentHandler $paymentHandler,
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): HandlerPrepareProcessStruct {
        // Extract data from transaction
        $order = $transaction->getOrder();
        $orderTransaction = $transaction->getOrderTransaction();
        $settings = $this->settingsFactory->getSettings($salesChannelContext->getSalesChannelId());

        try {
            $checkoutOrderCustomFields = $this->handleCheckoutOrderPayment(
                $paymentHandler,
                $transaction,
                $dataBag,
                $settings,
                $order,
                $orderTransaction,
                $salesChannelContext,
            );

            return new HandlerPrepareProcessStruct(
                $checkoutOrderCustomFields->getCheckoutReturnUrl(),
                $checkoutOrderCustomFields->getCheckoutPaymentId()
            );
        } catch (AsyncPaymentProcessException $exception) {
            // We catch AsyncPaymentProcessException exception because checkout.com fails to process the payment
            $this->logger->error(sprintf('Error when handle payment:  %s', $exception->getMessage()), [
                'function' => 'handleCheckoutOrderPayment',
            ]);

            // Update failed order status of Shopware
            $this->orderService->processTransition($order, $settings, CheckoutPaymentService::STATUS_FAILED, $salesChannelContext->getContext());

            // We keep throwing the exception to outside
            throw $exception;
        }
    }

    /**
     * Handle logic for payment process
     *
     * @note If the payment is failed at checkout.com, we have to throw AsyncPaymentProcessException exception
     *
     * @throws Exception
     * @throws ConstraintViolationException
     * @throws AsyncPaymentProcessException
     */
    private function handleCheckoutOrderPayment(
        PaymentHandler $paymentHandler,
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SettingStruct $settings,
        OrderEntity $order,
        OrderTransactionEntity $orderTransaction,
        SalesChannelContext $salesChannelContext
    ): OrderCustomFieldsStruct {
        $checkoutOrderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutOrderCustomFields->setTransactionReturnUrl($transaction->getReturnUrl());

        $checkoutPaymentId = $checkoutOrderCustomFields->getCheckoutPaymentId();

        // In case of an empty checkout payment ID, we create a new payment at Checkout.com
        // otherwise, we're getting the payment details from Checkout.com
        if (empty($checkoutPaymentId)) {
            $payment = $this->createCheckoutPayment($paymentHandler, $dataBag, $transaction, $order, $salesChannelContext);

            $checkoutOrderCustomFields->setCheckoutPaymentId($payment->getId());
            $checkoutOrderCustomFields->setCheckoutReturnUrl($payment->getRedirectUrl());
        } else {
            $payment = $this->checkoutPaymentService->getPaymentDetails($checkoutPaymentId, $salesChannelContext->getSalesChannelId());
            if ($payment->getStatus() === CheckoutPaymentService::STATUS_DECLINED) {
                $payment = $this->createCheckoutPayment($paymentHandler, $dataBag, $transaction, $order, $salesChannelContext);

                $checkoutOrderCustomFields->setCheckoutPaymentId($payment->getId());
                $checkoutOrderCustomFields->setCheckoutReturnUrl($payment->getRedirectUrl());
            }
        }

        // If the payment is not approved and status is not pending, throw an exception and log the error
        if (!$payment->isApproved() && $payment->getStatus() !== CheckoutPaymentService::STATUS_PENDING) {
            $this->logger->error('Checkout.com payment request failed', [
                'checkoutCom' => [
                    'paymentId' => $payment->getId(),
                    'actionId' => $payment->getActionId(),
                    'reference' => $payment->getReference(),
                    'status' => $payment->getStatus(),
                    'responseCode' => $payment->getResponseCode(),
                    'responseSummary' => $payment->getResponseSummary(),
                ],
                'orderId' => $order->getId(),
                'orderTransactionId' => $orderTransaction->getId(),
            ]);

            throw new AsyncPaymentProcessException($orderTransaction->getId(), 'Checkout.com payment request failed');
        }

        $paymentStatus = $payment->getStatus();

        // Update the order transaction of Shopware depending on checkout.com payment status
        $this->orderTransactionService->processTransition($orderTransaction, $paymentStatus, $salesChannelContext->getContext());

        // Update the order status of Shopware depending on checkout.com payment status
        $this->orderService->processTransition($order, $settings, $paymentStatus, $salesChannelContext->getContext());

        // After receiving data from the checkout.com response, insert as much data as possible into our "order" custom fields
        $this->orderService->updateCheckoutCustomFields($order, $checkoutOrderCustomFields, $salesChannelContext);

        return $checkoutOrderCustomFields;
    }

    /**
     * Get checkout payment request
     *
     * @throws ConstraintViolationException
     * @throws Exception
     */
    private function getCheckoutPaymentRequest(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        PaymentHandler $paymentHandler,
        OrderEntity $order,
        SalesChannelContext $context
    ): PaymentRequest {
        $currency = $this->orderExtractor->extractCurrency($order);
        $customer = $this->orderExtractor->extractCustomer($order, $context);

        $orderNumber = $order->getOrderNumber();
        if ($orderNumber === null) {
            $this->logger->error(sprintf('Order number is null with order ID: %s', $order->getId()), [
                'orderId' => $order->getId(),
            ]);

            throw new Exception('Order number is null');
        }

        $activeShippingAddress = $customer->getActiveShippingAddress();
        if ($activeShippingAddress === null) {
            $this->logger->error(sprintf('No active shipping address found with order ID: %s', $order->getId()), [
                'function' => 'getCheckoutPaymentRequest',
            ]);

            throw new Exception('No active shipping address found');
        }

        $paymentRequest = new PaymentRequest();

        // We add a success URL to the payment request, so we can redirect to Shopware after the payment
        // If the payment is failed, the page will redirect to complete payment page with error message
        $returnUrl = $this->generateReturnUrl($order->getId(), $context);
        $paymentRequest->success_url = $returnUrl;
        $paymentRequest->failure_url = $returnUrl;
        $paymentRequest->shipping = CheckoutComUtil::buildShipDetail($activeShippingAddress);

        if ($order->getTaxStatus() === CartPrice::TAX_STATE_FREE) {
            $paymentRequest->amount = CheckoutComUtil::formatPriceCheckout($order->getAmountNet(), $currency->getIsoCode());
        } else {
            $paymentRequest->amount = CheckoutComUtil::formatPriceCheckout($order->getAmountTotal(), $currency->getIsoCode());
        }

        // We uppercase the ISO code to avoid errors with checkout.com
        $paymentRequest->currency = strtoupper($currency->getIsoCode());

        // We disable auto `Capture` for the payment
        // We will `Capture` this payment in @finalize function
        $paymentRequest->capture = false;
        $paymentRequest->reference = $orderNumber;
        $paymentRequest->customer = CheckoutComUtil::buildCustomer($customer);

        // Prepare data for the payment depending on the payment method
        // Each method will have its own data
        return $paymentHandler->prepareDataForPay($paymentRequest, $dataBag, $order, $customer, $context);
    }

    /**
     * Create checkout payment by calling Checkout API
     *
     * @throws CheckoutApiException
     */
    private function createCheckoutPayment(
        PaymentHandler $paymentHandler,
        RequestDataBag $dataBag,
        AsyncPaymentTransactionStruct $transaction,
        OrderEntity $order,
        SalesChannelContext $salesChannelContext
    ): Payment {
        // Get the payment request, to call the Checkout API
        $paymentRequest = $this->getCheckoutPaymentRequest($transaction, $dataBag, $paymentHandler, $order, $salesChannelContext);

        $this->eventDispatcher->dispatch(new CheckoutRequestPaymentEvent($paymentRequest, $paymentHandler, $transaction, $salesChannelContext));

        // Call the API to create a payment at checkout.com
        return $this->checkoutPaymentService->requestPayment($paymentRequest, $salesChannelContext->getSalesChannelId());
    }

    private function generateReturnUrl(string $orderId, SalesChannelContext $context): string
    {
        $parameter = ['orderId' => $orderId];

        return $this->router->generate('payment.checkout-com.return.finalize.url', $parameter, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
