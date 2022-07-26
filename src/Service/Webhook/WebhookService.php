<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Webhook;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Facade\PaymentRefundFacade;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderService;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderTransactionService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Struct\WebhookReceiveDataStruct;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;

/**
 * This service will handle all webhook requests and update status of order and order transaction
 */
class WebhookService extends AbstractWebhookService
{
    private LoggerInterface $logger;

    private AbstractOrderService $orderService;

    private AbstractOrderTransactionService $orderTransactionService;

    private SettingsFactory $settingsFactory;

    private PaymentRefundFacade $paymentRefundFacade;

    public function __construct(
        LoggerInterface $logger,
        AbstractOrderService $orderService,
        AbstractOrderTransactionService $orderTransactionService,
        SettingsFactory $settingsFactory,
        PaymentRefundFacade $paymentRefundFacade
    ) {
        $this->logger = $logger;
        $this->orderService = $orderService;
        $this->orderTransactionService = $orderTransactionService;
        $this->settingsFactory = $settingsFactory;
        $this->paymentRefundFacade = $paymentRefundFacade;
    }

    public function getDecorated(): AbstractWebhookService
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Validate authorization token
     */
    public function authenticateToken(string $token, ?string $salesChannelId = null): bool
    {
        $webhookData = $this->settingsFactory->getWebhookConfig($salesChannelId);

        return $token === $webhookData->getAuthorization();
    }

    /**
     * Handling order and order transaction status based on the Checkout event type
     *
     * @throws Exception
     */
    public function handle(WebhookReceiveDataStruct $receiveDataStruct, Context $context, ?string $salesChannelId = null): void
    {
        $orderNumber = $receiveDataStruct->getReference();
        if ($orderNumber === null) {
            throw new CheckoutComException('Order number could not be null');
        }

        $order = $this->orderService->getOrderByOrderNumber($orderNumber, $context);

        $orderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        if ($orderCustomFields->getLastCheckoutActionId() === $receiveDataStruct->getActionId()) {
            $this->logger->warning('This action has already finished', [
                'actionId' => $receiveDataStruct->getActionId(),
            ]);

            return;
        }

        $orderTransactions = $order->getTransactions();
        if (!$orderTransactions instanceof OrderTransactionCollection) {
            throw new InvalidOrderException($order->getId());
        }

        $orderTransaction = $orderTransactions->last();
        if (!$orderTransaction instanceof OrderTransactionEntity) {
            throw new InvalidOrderException($order->getId());
        }

        $settings = $this->settingsFactory->getSettings($salesChannelId);
        $paymentStatus = $this->getPaymentStatusByEventType($receiveDataStruct->getType());

        if ($paymentStatus === CheckoutPaymentService::STATUS_REFUNDED) {
            $this->paymentRefundFacade->refundPaymentByWebhook($order, $receiveDataStruct, $context);

            return;
        }

        // Update the order transaction of Shopware depending on checkout.com payment status
        $this->orderTransactionService->processTransition($orderTransaction, $paymentStatus, $context);

        // Update the order status of Shopware depending on checkout.com payment status
        $this->orderService->processTransition($order, $settings, $paymentStatus, $context);
    }

    /**
     * Get checkout payment status based on event type
     *
     * @throws Exception
     */
    private function getPaymentStatusByEventType(?string $type): string
    {
        switch ($type) {
            case CheckoutWebhookService::PAYMENT_CAPTURED:
                return CheckoutPaymentService::STATUS_CAPTURED;

            case CheckoutWebhookService::PAYMENT_VOIDED:
                return CheckoutPaymentService::STATUS_VOID;

            case CheckoutWebhookService::PAYMENT_REFUNDED:
                return CheckoutPaymentService::STATUS_REFUNDED;

            case CheckoutWebhookService::PAYMENT_PENDING:
                return CheckoutPaymentService::STATUS_PENDING;

            case CheckoutWebhookService::PAYMENT_DECLINED:
                return CheckoutPaymentService::STATUS_DECLINED;

            case CheckoutWebhookService::PAYMENT_EXPIRED:
                return CheckoutPaymentService::STATUS_EXPIRED;

            case CheckoutWebhookService::PAYMENT_CANCELED:
                return CheckoutPaymentService::STATUS_CANCELED;
            default:
                $this->logger->critical('Unknown Checkout event type', [
                    'event' => $type,
                ]);

                throw new Exception(sprintf('Updating Status of Order not possible with event type: %s', $type));
        }
    }
}
