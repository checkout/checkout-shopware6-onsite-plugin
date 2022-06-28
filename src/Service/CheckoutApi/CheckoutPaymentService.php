<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\CheckoutApi;

use Checkout\CheckoutApiException;
use Checkout\Payments\PaymentRequest;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;

class CheckoutPaymentService extends AbstractCheckoutService
{
    public const STATUS_AUTHORIZED = 'Authorized';
    public const STATUS_VOID = 'Voided';
    public const STATUS_PENDING = 'Pending';
    public const STATUS_CARD_VERIFIED = 'Card Verified';
    public const STATUS_CAPTURED = 'Captured';
    public const STATUS_DECLINED = 'Declined';
    public const STATUS_REFUNDED = 'Refunded';
    public const STATUS_PAID = 'Paid';
    public const STATUS_FAILED = 'Failed';
    public const STATUS_CANCELED = 'Canceled';
    public const STATUS_EXPIRED = 'Expired';
    public const STATUS_PARTIALLY_CAPTURED = 'Partially Captured';
    public const STATUS_PARTIALLY_REFUNDED = 'Partially Refunded';

    /**
     * @throws CheckoutApiException
     */
    public function requestPayment(PaymentRequest $paymentRequest, string $salesChannelId): Payment
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $payment = $checkoutApi->getPaymentsClient()->requestPayment($paymentRequest);

            return (new Payment())->assign($payment);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * @throws CheckoutApiException
     */
    public function getPaymentDetails(string $paymentId, string $salesChannelId): Payment
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $paymentDetail = $checkoutApi->getPaymentsClient()->getPaymentDetails($paymentId);

            return (new Payment())->assign($paymentDetail);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__, ['paymentId' => $paymentId]);

            throw $e;
        }
    }

    /**
     * @throws CheckoutApiException
     */
    public function getPaymentActions(string $paymentId, string $salesChannelId): array
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $paymentAction = $checkoutApi->getPaymentsClient()->getPaymentActions($paymentId);

            return $paymentAction['items'] ?? [];
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__, ['paymentId' => $paymentId]);

            throw $e;
        }
    }

    /**
     * @throws CheckoutApiException
     */
    public function capturePayment(string $paymentId, string $salesChannelId): void
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $checkoutApi->getPaymentsClient()->capturePayment($paymentId);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__, ['paymentId' => $paymentId]);

            throw $e;
        }
    }

    /**
     * @throws CheckoutApiException
     */
    public function refundPayment(string $paymentId, string $salesChannelId): void
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $checkoutApi->getPaymentsClient()->refundPayment($paymentId);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__, ['paymentId' => $paymentId]);

            throw $e;
        }
    }
}
