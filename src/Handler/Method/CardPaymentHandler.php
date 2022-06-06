<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\AbstractRequestSource;
use Checkout\Payments\Source\RequestIdSource;
use Checkout\Payments\Source\RequestTokenSource;
use CheckoutCom\Shopware6\Exception\CheckoutInvalidTokenException;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CardPaymentHandler extends PaymentHandler
{
    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.cardPaymentsLabel';
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
        SalesChannelContext $context
    ): PaymentRequest {
        $this->enableThreeDsRequest($paymentRequest, $context->getSalesChannelId());

        $paymentRequest->source = $this->buildSource($dataBag, $order, $context);

        return $paymentRequest;
    }

    /**
     * Build source to call the Checkout.com API
     *
     * @throws Exception
     */
    private function buildSource(RequestDataBag $dataBag, OrderEntity $order, SalesChannelContext $context): AbstractRequestSource
    {
        $requestIdSource = $this->getRequestIdSource($dataBag);

        // If the data bag has a SourceId, we use it to build source
        if ($requestIdSource instanceof RequestIdSource) {
            return $requestIdSource;
        }

        $token = RequestUtil::getTokenPayment($dataBag);
        if (!\is_string($token)) {
            throw new CheckoutInvalidTokenException(static::getPaymentMethodType());
        }

        $billingAddress = $this->orderExtractor->extractBillingAddress($order, $context);

        $requestTokenSource = new RequestTokenSource();
        $requestTokenSource->token = $token;
        $requestTokenSource->billing_address = CheckoutComUtil::buildAddress($billingAddress);

        return $requestTokenSource;
    }
}
