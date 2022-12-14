<?php
declare(strict_types=1);

namespace Cko\Shopware6\Handler\Method;

use Checkout\Common\Country;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\RequestIdSource;
use Checkout\Sources\Previous\SepaSourceRequest;
use Checkout\Sources\Previous\SourceData;
use Checkout\Sources\Previous\SourceType;
use Cko\Shopware6\Exception\CheckoutInvalidSourceException;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Helper\CheckoutComUtil;
use Cko\Shopware6\Helper\RequestUtil;
use Cko\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class SepaHandler extends PaymentHandler
{
    public const MANDATE_TYPE_ONE_OFF = 'single';
    public const MANDATE_TYPE_RECURRING = 'recurring';

    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'SEPA Direct Debit');

        return $displayNames;
    }

    // Hide this payment method on account type NAS because checkout.com haven't supported yet
    public function shouldHideByAccountType(string $accountType): bool
    {
        return $accountType === SettingStruct::ACCOUNT_TYPE_NAS;
    }

    public static function getPaymentMethodType(): string
    {
        return SourceType::$sepa;
    }

    public function getAvailableCountries(): array
    {
        return [
            Country::$DE,
            Country::$HU,
            Country::$FR,
            Country::$IT,
        ];
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SettingStruct $settings,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildIdSource($dataBag, $order, $context);

        return $paymentRequest;
    }

    /**
     * Build request source to call the Checkout.com API
     */
    private function buildIdSource(RequestDataBag $dataBag, OrderEntity $order, SalesChannelContext $context): RequestIdSource
    {
        $request = new RequestIdSource();
        $request->id = $this->getSourceId($dataBag, $order, $context);

        return $request;
    }

    /**
     * Call to the Checkout.com API to get mandate source
     */
    private function getSourceId(RequestDataBag $dataBag, OrderEntity $order, SalesChannelContext $context): string
    {
        $orderNumber = $this->orderExtractor->extractOrderNumber($order);
        $requestSource = new SepaSourceRequest();
        $requestSource->source_data = $this->buildSourceData($dataBag, $orderNumber);
        $requestSource->reference = $orderNumber;
        $requestSource->billing_address = CheckoutComUtil::buildAddress($this->orderExtractor->extractBillingAddress($order, $context));
        $requestSource->customer = CheckoutComUtil::buildCustomer($this->orderExtractor->extractCustomer($order));

        $source = $this->checkoutSourceService->createSepaSource($requestSource, $context->getSalesChannelId());

        return $source->getId();
    }

    /**
     * Build source data of mandate request
     */
    private function buildSourceData(RequestDataBag $dataBag, string $orderNumber): SourceData
    {
        $sourceData = RequestUtil::getSource($dataBag);
        if (!$sourceData instanceof RequestDataBag) {
            throw new CheckoutInvalidSourceException(static::getPaymentMethodType());
        }

        $sourceData = $sourceData->all();
        $definition = $this->getValidationDefinition();
        $this->dataValidator->validate($sourceData, $definition);

        $source = new SourceData();
        $source->first_name = $sourceData['firstName'];
        $source->last_name = $sourceData['lastName'];
        $source->account_iban = \strtoupper($sourceData['iban']);
        $source->billing_descriptor = $this->translator->trans('checkoutCom.components.sepa.billingDescriptor', ['%orderNumber%' => $orderNumber]);
        $source->mandate_type = self::MANDATE_TYPE_ONE_OFF;

        return $source;
    }

    private function getValidationDefinition(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.payment_handler.sepa');

        $definition->add('firstName', new Type('string'), new NotBlank())
            ->add('lastName', new Type('string'), new NotBlank())
            ->add('iban', new Type('string'), new NotBlank());

        return $definition;
    }
}
