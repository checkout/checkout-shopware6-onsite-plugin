<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\RequestTokenSource;
use Checkout\Tokens\GooglePayTokenData;
use Checkout\Tokens\GooglePayTokenRequest;
use Checkout\Tokens\TokenType;
use CheckoutCom\Shopware6\Exception\CheckoutInvalidTokenException;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;
use CheckoutCom\Shopware6\Struct\DirectPay\Cart\DirectPayCartItemStruct;
use CheckoutCom\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use CheckoutCom\Shopware6\Struct\DirectPay\GooglePay\GooglePayLineItemCollection;
use CheckoutCom\Shopware6\Struct\DirectPay\GooglePay\GooglePayLineItemStruct;
use CheckoutCom\Shopware6\Struct\DirectPay\GooglePay\GoogleShippingOptionCollection;
use CheckoutCom\Shopware6\Struct\DirectPay\GooglePay\GoogleShippingOptionStruct;
use CheckoutCom\Shopware6\Struct\DirectPay\GooglePay\GoogleShippingPayloadStruct;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class GooglePayHandler extends PaymentHandler
{
    public const ENV_TEST = 'TEST';
    public const ENV_PRODUCTION = 'PRODUCTION';

    public const BUTTON_BLACK = 'black';
    public const BUTTON_WHITE = 'white';

    public const TYPE_LINE_ITEM = 'LINE_ITEM';
    public const TYPE_SUB_TOTAL = 'SUBTOTAL';
    public const TYPE_TAX = 'TAX';

    public static function getPaymentMethodType(): string
    {
        return TokenType::$googlepay;
    }

    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'Google Pay');

        return $displayNames;
    }

    public function getDirectShippingOptions(): AbstractShippingOptionCollection
    {
        return new GoogleShippingOptionCollection();
    }

    public function formatDirectShippingOption(
        ShippingMethodEntity $shippingMethodEntity,
        float $shippingCostsPrice,
        SalesChannelContext $context
    ): GoogleShippingOptionStruct {
        $shippingMethodDescription = '';
        $shippingDeliveryTime = $shippingMethodEntity->getDeliveryTime();
        if ($shippingDeliveryTime instanceof DeliveryTimeEntity && !empty($shippingDeliveryTime->getName())) {
            // Modify the shipping method detail to include the delivery time
            $shippingMethodDescription = sprintf(' (%s)', $shippingDeliveryTime->getName());
        }

        $shippingCostsPriceFormatted = $this->currencyFormatter->formatCurrencyByLanguage(
            $shippingCostsPrice,
            $context->getCurrency()->getIsoCode(),
            $context->getLanguageId(),
            $context->getContext()
        );

        $shippingMethod = new GoogleShippingOptionStruct();
        $shippingMethod->setId($shippingMethodEntity->getId());
        $shippingMethod->setLabel(sprintf('%s: %s', $shippingCostsPriceFormatted, $shippingMethodEntity->getName()));
        $shippingMethod->setDescription($shippingMethodEntity->getDescription() . $shippingMethodDescription);

        return $shippingMethod;
    }

    /**
     * Return shipping payload with all necessary information for Google Pay
     */
    public function getDirectShippingPayload(
        ?AbstractShippingOptionCollection $shippingMethods,
        DirectPayCartStruct $directPayCart,
        SalesChannelContext $context
    ): GoogleShippingPayloadStruct {
        $shippingPayLoad = new GoogleShippingPayloadStruct();

        /*
         * SHIPPING METHODS
         */
        if ($shippingMethods instanceof AbstractShippingOptionCollection) {
            $shippingPayLoad->setShippingOptions($shippingMethods);
        }

        /*
         * NEW TOTAL
         */
        $shippingPayLoad->setTotalPrice((string) ($directPayCart->getTotalAmount()));

        /**
         * NEW LINE ITEMS
         */
        $lineItems = new GooglePayLineItemCollection();

        // Sub total
        $lineItems->add(new GooglePayLineItemStruct(
            self::TYPE_SUB_TOTAL,
            $this->translator->trans('checkoutCom.payments.subtotalLabel'),
            (string) ($directPayCart->getLineItemAmount()),
        ));

        // Shipping
        foreach ($directPayCart->getShipping() as $shipping) {
            $lineItems->add(new GooglePayLineItemStruct(
                self::TYPE_LINE_ITEM,
                $shipping->getName(),
                (string) ($shipping->getPrice()),
            ));
        }

        // Taxes
        $directCartTax = $directPayCart->getTax();
        if ($directCartTax instanceof DirectPayCartItemStruct) {
            $lineItems->add(new GooglePayLineItemStruct(
                self::TYPE_TAX,
                $this->translator->trans('checkoutCom.payments.taxesLabel'),
                (string) ($directCartTax->getPrice()),
            ));
        }

        $shippingPayLoad->setDisplayItems($lineItems);

        return $shippingPayLoad;
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SettingStruct $settings,
        SalesChannelContext $context
    ): PaymentRequest {
        $this->enableThreeDsRequest($dataBag, $paymentRequest, $context->getSalesChannelId());
        $paymentRequest->source = $this->buildTokenSource($dataBag, $context);

        return $paymentRequest;
    }

    /**
     * Build token source to call the Checkout.com API
     *
     * @throws Exception
     */
    private function buildTokenSource(RequestDataBag $dataBag, SalesChannelContext $context): RequestTokenSource
    {
        $tokenRequest = RequestUtil::getTokenPayment($dataBag);
        if (!$tokenRequest instanceof RequestDataBag) {
            throw new CheckoutInvalidTokenException(static::getPaymentMethodType());
        }

        $token = $this->getTokenFromRequest($tokenRequest->all(), $context);

        $requestTokenSource = new RequestTokenSource();
        $requestTokenSource->token = $token;

        return $requestTokenSource;
    }

    /**
     * Get the token string to create the checkout.com payment
     *
     * @throws ConstraintViolationException
     * @throws Exception
     */
    private function getTokenFromRequest(array $tokenRequest, SalesChannelContext $context): string
    {
        $definition = $this->getValidationDefinition();
        $this->dataValidator->validate($tokenRequest, $definition);

        $walletTokenRequest = new GooglePayTokenRequest();
        $walletTokenRequest->token_data = $this->getTokenData($tokenRequest);

        // Call the Checkout.com API to get the token
        $checkoutToken = $this->checkoutTokenService->requestWalletToken($walletTokenRequest, $context->getSalesChannelId());

        return $checkoutToken->getToken();
    }

    /**
     * Get Google Pay token data from payment data of Google Pay request
     * It always has the correct keys (protocolVersion, signature, signature)
     * Because we've already validated it
     */
    private function getTokenData(array $requestTokenData): GooglePayTokenData
    {
        $tokenData = new GooglePayTokenData();
        $tokenData->protocolVersion = $requestTokenData['protocolVersion'];
        $tokenData->signedMessage = $requestTokenData['signedMessage'];
        $tokenData->signature = $requestTokenData['signature'];

        return $tokenData;
    }

    private function getValidationDefinition(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('payment_handler.google_pay');

        $definition->add('protocolVersion', new Type('string'), new NotBlank());
        $definition->add('signedMessage', new Type('string'), new NotBlank());
        $definition->add('signature', new Type('string'), new NotBlank());

        return $definition;
    }
}
