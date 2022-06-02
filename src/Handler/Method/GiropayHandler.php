<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\Apm\RequestGiropaySource;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GiropayHandler extends PaymentHandler
{
    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.giropayLabel';
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$giropay;
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildGiropaySource($order);

        return $paymentRequest;
    }

    /**
     * Build token source to call the Checkout.com API
     *
     * @throws \Exception
     */
    private function buildGiropaySource(OrderEntity $order): RequestGiropaySource
    {
        $request = new RequestGiropaySource();
        $request->purpose = \sprintf('order_%s', $this->orderExtractor->extractOrderNumber($order));

        return $request;
    }
}
