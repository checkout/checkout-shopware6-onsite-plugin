<?php
declare(strict_types=1);

namespace Cko\Shopware6\Handler\Method;

use Checkout\Common\Country;
use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\Apm\RequestSofortSource;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SofortHandler extends PaymentHandler
{
    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'Sofort');

        return $displayNames;
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$sofort;
    }

    public function getAvailableCountries(): array
    {
        return [
            Country::$DE,
        ];
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SettingStruct $settings,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = new RequestSofortSource();

        return $paymentRequest;
    }
}
