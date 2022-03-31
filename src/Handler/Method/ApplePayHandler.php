<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Tokens\TokenType;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ApplePayHandler extends PaymentHandler
{
    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.applePayLabel';
    }

    public static function getPaymentMethodType(): string
    {
        return TokenType::$applepay;
    }

    public function prepareDataForPay(PaymentRequest $paymentRequest, OrderEntity $order, CustomerEntity $customer, SalesChannelContext $context): PaymentRequest
    {
        // @TODO: Implement prepare data for apple pay or just return.
        return $paymentRequest;
    }
}
