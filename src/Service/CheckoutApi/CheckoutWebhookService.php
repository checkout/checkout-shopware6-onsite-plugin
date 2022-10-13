<?php
declare(strict_types=1);

namespace Cko\Shopware6\Service\CheckoutApi;

use Checkout\CheckoutApiException;
use Checkout\Webhooks\Previous\WebhookRequest;
use Cko\Shopware6\Factory\CheckoutApiFactory;
use Cko\Shopware6\Struct\CheckoutApi\Webhook;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * This service provides functions to manage webhooks from Checkout
 */
class CheckoutWebhookService extends AbstractCheckoutService
{
    // Do not modify this const, we only use one webhook to handle all checkout event
    public const WEBHOOK_ROUTE_NAME = 'api.action.checkout-com.payment.webhooks';

    public const CARD_VERIFICATION_DECLINED = 'card_verification_declined';
    public const CARD_VERIFIED = 'card_verified';
    public const DISPUTE_CANCELED = 'dispute_canceled';
    public const DISPUTE_EVIDENCE_REQUIRED = 'dispute_evidence_required';
    public const DISPUTE_EXPIRED = 'dispute_expired';
    public const DISPUTE_LOST = 'dispute_lost';
    public const DISPUTE_RESOLVED = 'dispute_resolved';
    public const DISPUTE_WON = 'dispute_won';
    public const PAYMENT_APPROVED = 'payment_approved';
    public const PAYMENT_CANCELED = 'payment_canceled';
    public const PAYMENT_CAPTURE_DECLINED = 'payment_capture_declined';
    public const PAYMENT_CAPTURE_PENDING = 'payment_capture_pending';
    public const PAYMENT_CAPTURED = 'payment_captured';
    public const PAYMENT_CHARGEBACK = 'payment_chargeback';
    public const PAYMENT_DECLINED = 'payment_declined';
    public const PAYMENT_EXPIRED = 'payment_expired';
    public const PAYMENT_PAID = 'payment_paid';
    public const PAYMENT_PENDING = 'payment_pending';
    public const PAYMENT_REFUND_DECLINED = 'payment_refund_declined';
    public const PAYMENT_REFUND_PENDING = 'payment_refund_pending';
    public const PAYMENT_REFUNDED = 'payment_refunded';
    public const PAYMENT_RETRIEVAL = 'payment_retrieval';
    public const PAYMENT_VOID_DECLINED = 'payment_void_declined';
    public const PAYMENT_VOIDED = 'payment_voided';
    public const SOURCE_UPDATED = 'source_updated';

    public const WEBHOOK_EVENTS = [
        self::PAYMENT_CAPTURED,
        self::PAYMENT_VOIDED,
        self::PAYMENT_REFUNDED,
        self::PAYMENT_PENDING,
        self::PAYMENT_DECLINED,
        self::PAYMENT_EXPIRED,
        self::PAYMENT_CANCELED,
    ];

    private RouterInterface $router;

    public function __construct(
        LoggerInterface $logger,
        CheckoutApiFactory $checkoutApiFactory,
        RouterInterface $router
    ) {
        parent::__construct($logger, $checkoutApiFactory);

        $this->router = $router;
    }

    /**
     * Add a new webhook to Checkout
     *
     * @throws CheckoutApiException
     */
    public function registerWebhook(?string $salesChannelId = null): Webhook
    {
        $checkoutApi = $this->checkoutApiFactory->getPreviousClient($salesChannelId);

        try {
            $webhook = $checkoutApi->getWebhooksClient()->registerWebhook($this->buildWebhookRequest($salesChannelId));

            return $this->buildWebhook($webhook);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * Get a webhook by id
     *
     * @throws CheckoutApiException
     */
    public function retrieveWebhook(string $webhookId, ?string $salesChannelId = null): void
    {
        $checkoutApi = $this->checkoutApiFactory->getPreviousClient($salesChannelId);

        try {
            $checkoutApi->getWebhooksClient()->retrieveWebhook($webhookId);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    private function buildWebhookRequest(
        ?string $salesChannelId = null,
        string $routeName = self::WEBHOOK_ROUTE_NAME,
        array $eventTypes = self::WEBHOOK_EVENTS
    ): WebhookRequest {
        $request = new WebhookRequest();
        $request->url = $this->router->generate(
            $routeName,
            ['salesChannelId' => $salesChannelId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $request->content_type = 'json';
        $request->event_types = $eventTypes;
        $request->active = true;

        return $request;
    }

    private function buildWebhook(array $data): Webhook
    {
        $webhook = new Webhook();
        $webhook->setId($data['id'] ?? null);
        $webhook->setAuthorization(
            isset($data['headers'])
                ? $data['headers']['authorization'] ?? null
                : null
        );

        return $webhook;
    }
}
