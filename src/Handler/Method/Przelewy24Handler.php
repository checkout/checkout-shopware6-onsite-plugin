<?php
declare(strict_types=1);

namespace Cko\Shopware6\Handler\Method;

use Checkout\Common\Country;
use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\Apm\RequestP24Source;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class Przelewy24Handler extends PaymentHandler
{
    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'Przelewy24');

        return $displayNames;
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$przelewy24;
    }

    public function getAvailableCountries(): array
    {
        return [
            Country::$PL,
        ];
    }

    /**
     * @throws Exception
     */
    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SettingStruct $settings,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildPrzelewy24Source($order);

        return $paymentRequest;
    }

    /**
     * Build request source to call the Checkout.com API
     *
     * @throws Exception
     */
    private function buildPrzelewy24Source(OrderEntity $order): RequestP24Source
    {
        $orderCustomer = $order->getOrderCustomer();
        if (!$orderCustomer instanceof OrderCustomerEntity) {
            $message = \sprintf('Could not get customer info from order ID: %s', $order->getId());
            $this->logger->error($message, [
                'function' => __FUNCTION__,
            ]);

            throw new Exception($message);
        }

        $request = new RequestP24Source();
        $request->payment_country = Country::$PL;
        $request->account_holder_name = \sprintf('%s %s', $orderCustomer->getFirstName(), $orderCustomer->getLastName());
        $request->account_holder_email = $orderCustomer->getEmail();

        return $request;
    }
}
