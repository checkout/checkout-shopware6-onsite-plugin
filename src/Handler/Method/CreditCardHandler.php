<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestTokenSource;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use Exception;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
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
    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        CustomerEntity $customer,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildTokenSource($dataBag, $customer);

        return $paymentRequest;
    }

    /**
     * Build own credit card token source to call the Checkout.com API
     *
     * @throws Exception
     */
    private function buildTokenSource(RequestDataBag $dataBag, CustomerEntity $customer): RequestTokenSource
    {
        $token = RequestUtil::getTokenPayment($dataBag);
        if (!\is_string($token)) {
            throw new Exception('Invalid credit card token');
        }

        $requestTokenSource = new RequestTokenSource();
        $requestTokenSource->token = $token;
        $requestTokenSource->billing_address = CheckoutComUtil::buildAddress($customer->getDefaultBillingAddress());

        return $requestTokenSource;
    }
}
