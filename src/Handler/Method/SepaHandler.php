<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestIdSource;
use Checkout\Sources\SourceType;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SepaHandler extends PaymentHandler
{
    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.sepaLabel';
    }

    public static function getPaymentMethodType(): string
    {
        return SourceType::$sepa;
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = new RequestIdSource();

        return $paymentRequest;
    }
}
