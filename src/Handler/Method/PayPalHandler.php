<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\Apm\RequestPayPalSource;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayPalHandler extends PaymentHandler
{
    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'PayPal');

        return $displayNames;
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
     * Build request source to call the Checkout.com API
     */
    private function buildPayPalSource(OrderEntity $order): RequestPayPalSource
    {
        $source = new RequestPayPalSource();
        $source->invoice_number = CheckoutComUtil::buildReference($order);

        return $source;
    }
}
