<?php
declare(strict_types=1);

namespace Cko\Shopware6\Handler\Method;

use Checkout\Common\Country;
use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\Apm\RequestIdealSource;
use Cko\Shopware6\Exception\CheckoutInvalidSourceException;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Helper\RequestUtil;
use Cko\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class IdealHandler extends PaymentHandler
{
    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'iDEAL');

        return $displayNames;
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$ideal;
    }

    public function getAvailableCountries(): array
    {
        return [
            Country::$NL,
            Country::$HU,
        ];
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SettingStruct $settings,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildIDealSource($dataBag, $order);

        return $paymentRequest;
    }

    /**
     * Build request source to call the Checkout.com API
     */
    private function buildIDealSource(RequestDataBag $dataBag, OrderEntity $order): RequestIdealSource
    {
        $sourceData = RequestUtil::getSource($dataBag);
        if (!$sourceData instanceof RequestDataBag) {
            throw new CheckoutInvalidSourceException(static::getPaymentMethodType());
        }

        $sourceData = $sourceData->all();
        $definition = $this->getValidationDefinition();
        $this->dataValidator->validate($sourceData, $definition);

        $orderNumber = $this->orderExtractor->extractOrderNumber($order);

        $request = new RequestIdealSource();
        $request->bic = $sourceData['bic'];
        $request->description = \sprintf('ORD%s', $orderNumber);

        return $request;
    }

    private function getValidationDefinition(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.payment_handler.ideal');
        $definition->add('bic', new Type('string'), new NotBlank());

        return $definition;
    }
}
