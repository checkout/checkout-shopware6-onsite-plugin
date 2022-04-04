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
    public const STATUS_CARD_VERIFIED = 'Card Verified"';
    public const STATUS_CAPTURED = 'Captured';
    public const STATUS_DECLINED = 'Declined';
    public const STATUS_REFUNDED = 'Refunded';
    public const STATUS_PAID = 'Paid';
    public const STATUS_FAILED = 'Failed';

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
            $errorMessage = $this->modifyAndLogMessage($e, __FUNCTION__);

            throw new CheckoutApiException($errorMessage);
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
            $errorMessage = $this->modifyAndLogMessage($e, __FUNCTION__, ['paymentId' => $paymentId]);

            throw new CheckoutApiException($errorMessage);
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
            $errorMessage = $this->modifyAndLogMessage($e, __FUNCTION__, ['paymentId' => $paymentId]);

            throw new CheckoutApiException($errorMessage);
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
            $errorMessage = $this->modifyAndLogMessage($e, __FUNCTION__, ['paymentId' => $paymentId]);

            throw new CheckoutApiException($errorMessage);
        }
    }
}
