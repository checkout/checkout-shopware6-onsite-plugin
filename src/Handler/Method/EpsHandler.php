<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\Apm\RequestEpsSource;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class EpsHandler extends PaymentHandler
{
    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.epsLabel';
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$eps;
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
        $paymentRequest->source = $this->buildEpsSource($order);

        return $paymentRequest;
    }

    /**
     * Build request source to call the Checkout.com API
     */
    private function buildEpsSource(OrderEntity $order): RequestEpsSource
    {
        $source = new RequestEpsSource();
        $source->purpose = \sprintf('order_%s', $this->orderExtractor->extractOrderNumber($order));

        return $source;
    }
}
