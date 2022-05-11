<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Handler\Source\RequestPrzelewy24Source;
use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class Przelewy24Handler extends PaymentHandler
{
    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.p24Label';
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$przelewy24;
    }

    /**
     * @throws Exception
     */
    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildPrzelewy24Source($order);

        return $paymentRequest;
    }

    /**
     * Build token source to call the Checkout.com API
     *
     * @throws Exception
     */
    private function buildPrzelewy24Source(OrderEntity $order): RequestPrzelewy24Source
    {
        $orderCustomer = $order->getOrderCustomer();
        if (!$orderCustomer instanceof OrderCustomerEntity) {
            $message = \sprintf('Could not get customer info from order ID: %s', $order->getId());
            $this->logger->error($message, [
                'function' => __FUNCTION__,
            ]);

            throw new Exception($message);
        }

        $request = new RequestPrzelewy24Source();
        $request->account_holder_name = \sprintf('%s %s', $orderCustomer->getFirstName(), $orderCustomer->getLastName());
        $request->account_holder_email = $orderCustomer->getEmail();

        return $request;
    }
}
