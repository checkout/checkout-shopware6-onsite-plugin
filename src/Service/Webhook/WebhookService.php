<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Webhook;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use CheckoutCom\Shopware6\Struct\WebhookReceiveDataStruct;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;

/**
 * This service will handle all webhook request and update status of order and order transaction
 */
class WebhookService extends AbstractWebhookService
{
    private LoggerInterface $logger;

    private OrderService $orderService;

    private OrderTransactionService $orderTransactionService;

    private SettingsFactory $settingsFactory;

    public function __construct(
        LoggerInterface $logger,
        OrderService $orderService,
        OrderTransactionService $orderTransactionService,
        SettingsFactory $settingsFactory
    ) {
        $this->logger = $logger;
        $this->orderService = $orderService;
        $this->orderTransactionService = $orderTransactionService;
        $this->settingsFactory = $settingsFactory;
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
     * Handling order and order transaction status base on Checkout event type
     *
     * @throws Exception
     */
    public function handle(WebhookReceiveDataStruct $receiveDataStruct, Context $context, ?string $salesChannelId = null): void
    {
        $order = $this->orderService->getOrderByOrderNumber($receiveDataStruct->getReference(), $context);

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
    private function getPaymentStatusByEventType(string $type): string
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
            default:
                $this->logger->critical('Unknown Checkout event type', [
                    'event' => $type,
                ]);

                throw new Exception(sprintf('Updating Status of Order not possible with event type: %s', $type));
        }
    }
}
