<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestTokenSource;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Service\CustomerService;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CreditCardHandler extends PaymentHandler
{
    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$card;
    }

    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNameCollection = new DisplayNameTranslationCollection();
        $displayNameCollection->addLangData('de-DE', 'Kreditkarte');
        $displayNameCollection->addLangData('en-GB', 'Credit card');

        return $displayNameCollection;
    }

    public function prepareDataForPay(PaymentRequest $paymentRequest, OrderEntity $order, CustomerEntity $customer, SalesChannelContext $context): PaymentRequest
    {
        $paymentRequest->source = $this->buildTokenSource($customer);

        return $paymentRequest;
    }

    /**
     * Build own credit card token source to call the Checkout.com API
     */
    private function buildTokenSource(CustomerEntity $customer): RequestTokenSource
    {
        $checkoutCustomerCustomFields = CustomerService::getCheckoutCustomerCustomFields($customer);

        $requestTokenSource = new RequestTokenSource();
        $requestTokenSource->token = $checkoutCustomerCustomFields->getCardToken();
        $requestTokenSource->billing_address = CheckoutComUtil::buildAddress($customer->getDefaultBillingAddress());

        return $requestTokenSource;
    }
}
