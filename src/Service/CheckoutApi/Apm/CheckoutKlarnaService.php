<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\CheckoutApi\Apm;

use Checkout\Apm\Previous\Klarna\CreditSessionRequest;
use Checkout\Apm\Previous\Klarna\OrderCaptureRequest;
use Checkout\CheckoutApiException;
use Checkout\Payments\VoidRequest;
use Cko\Shopware6\Service\CheckoutApi\AbstractCheckoutService;
use Cko\Shopware6\Struct\PaymentMethod\Klarna\CreditSessionStruct;

class CheckoutKlarnaService extends AbstractCheckoutService
{
    /**
     * Call Checkout Klarna API to create credit session
     *
     * @throws CheckoutApiException
     */
    public function createCreditSession(CreditSessionRequest $creditSessionRequest, string $salesChannelId): CreditSessionStruct
    {
        $checkoutApi = $this->checkoutApiFactory->getPreviousClient($salesChannelId);

        try {
            $response = $checkoutApi->getKlarnaClient()->createCreditSession($creditSessionRequest);

            return (new CreditSessionStruct())->assign($response);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * Call Checkout Klarna API to capture the payment
     *
     * @throws CheckoutApiException
     */
    public function capturePayment(string $paymentId, OrderCaptureRequest $orderCaptureRequest, string $salesChannelId): string
    {
        $checkoutApi = $this->checkoutApiFactory->getPreviousClient($salesChannelId);

        try {
            $captureResponse = $checkoutApi->getKlarnaClient()->capturePayment($paymentId, $orderCaptureRequest);

            return $captureResponse['action_id'];
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * Call Checkout Klarna API to void the payment
     *
     * @throws CheckoutApiException
     */
    public function voidPayment(string $paymentId, VoidRequest $voidRequest, string $salesChannelId): string
    {
        $checkoutApi = $this->checkoutApiFactory->getPreviousClient($salesChannelId);

        try {
            $voidResponse = $checkoutApi->getKlarnaClient()->voidPayment($paymentId, $voidRequest);

            return $voidResponse['action_id'];
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }
}
