<?php declare(strict_types=1);

namespace Cko\Shopware6\Handler\Method;

use Checkout\Common\Country;
use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\Apm\RequestGiropaySource;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GiropayHandler extends PaymentHandler
{
    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'Giropay');

        return $displayNames;
    }

    public function getAvailableCountries(): array
    {
        return [
            Country::$DE,
        ];
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$giropay;
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SettingStruct $settings,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildGiropaySource($order);

        return $paymentRequest;
    }

    /**
     * Build request source to call the Checkout.com API
     */
    private function buildGiropaySource(OrderEntity $order): RequestGiropaySource
    {
        $request = new RequestGiropaySource();
        $request->purpose = \sprintf('order_%s', $this->orderExtractor->extractOrderNumber($order));

        return $request;
    }
}
