<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestTokenSource;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Service\CustomerService;
use Exception;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CreditCardHandler extends PaymentHandler
{
    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.creditCardLabel';
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$card;
    }

    /**
     * @throws Exception
     */
    public function prepareDataForPay(PaymentRequest $paymentRequest, OrderEntity $order, CustomerEntity $customer, SalesChannelContext $context): PaymentRequest
    {
        $paymentRequest->source = $this->buildTokenSource($customer);

        return $paymentRequest;
    }

    /**
     * Build own credit card token source to call the Checkout.com API
     *
     * @throws Exception
     */
    private function buildTokenSource(CustomerEntity $customer): RequestTokenSource
    {
        $checkoutCustomerCustomFields = CustomerService::getCheckoutCustomerCustomFields($customer);
        $token = $checkoutCustomerCustomFields->getCardToken();
        if ($token === null) {
            throw new Exception('No card token found for Credit Card payment method');
        }

        $requestTokenSource = new RequestTokenSource();
        $requestTokenSource->token = $token;
        $requestTokenSource->billing_address = CheckoutComUtil::buildAddress($customer->getDefaultBillingAddress());

        return $requestTokenSource;
    }
}
