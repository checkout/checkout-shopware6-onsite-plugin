<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Tokens\TokenType;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GooglePayHandler extends PaymentHandler
{
    public const ENV_TEST = 'TEST';
    public const ENV_PRODUCTION = 'PRODUCTION';

    public static function getPaymentMethodType(): string
    {
        return TokenType::$googlepay;
    }

    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.googlePayLabel';
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SalesChannelContext $context
    ): PaymentRequest {
        // @TODO: Implement prepareDataForPay() method for Google Pay
        return $paymentRequest;
    }
}
