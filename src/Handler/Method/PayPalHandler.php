<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Handler\Source\RequestPayPalSource;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayPalHandler extends PaymentHandler
{
    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.payPalLabel';
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$paypal;
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
        $paymentRequest->source = $this->buildPayPalSource($order);

        return $paymentRequest;
    }

    /**
     * Build own PayPal source to call the Checkout.com API
     *
     * @throws Exception
     */
    private function buildPayPalSource(OrderEntity $order): RequestPayPalSource
    {
        $source = new RequestPayPalSource();
        $source->invoice_number = CheckoutComUtil::buildReference($order);

        return $source;
    }
}
