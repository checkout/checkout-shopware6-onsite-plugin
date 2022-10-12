<?php declare(strict_types=1);

namespace Cko\Shopware6\Handler\Method;

use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\RequestTokenSource;
use Checkout\Tokens\ApplePayTokenData;
use Checkout\Tokens\ApplePayTokenRequest;
use Checkout\Tokens\TokenType;
use Cko\Shopware6\Exception\CheckoutInvalidTokenException;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Helper\RequestUtil;
use Cko\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;
use Cko\Shopware6\Struct\DirectPay\ApplePay\ApplePayLineItemCollection;
use Cko\Shopware6\Struct\DirectPay\ApplePay\ApplePayLineItemStruct;
use Cko\Shopware6\Struct\DirectPay\ApplePay\AppleShippingOptionCollection;
use Cko\Shopware6\Struct\DirectPay\ApplePay\AppleShippingOptionStruct;
use Cko\Shopware6\Struct\DirectPay\ApplePay\AppleShippingPayloadStruct;
use Cko\Shopware6\Struct\DirectPay\Cart\DirectPayCartItemStruct;
use Cko\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use Cko\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class ApplePayHandler extends PaymentHandler
{
    public const REQUEST_PAYMENT_DATA = 'paymentData';
    public const LINE_ITEM_TYPE = 'final';

    public static function getPaymentMethodType(): string
    {
        return TokenType::$applepay;
    }

    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'Apple Pay');

        return $displayNames;
    }

    public function getDirectShippingOptions(): AbstractShippingOptionCollection
    {
        return new AppleShippingOptionCollection();
    }

    public function formatDirectShippingOption(
        ShippingMethodEntity $shippingMethodEntity,
        float $shippingCostsPrice,
        SalesChannelContext $context
    ): AppleShippingOptionStruct {
        $shippingMethodDetail = $shippingMethodEntity->getDescription();
        $shippingDeliveryTime = $shippingMethodEntity->getDeliveryTime();
        if ($shippingDeliveryTime instanceof DeliveryTimeEntity && !empty($shippingDeliveryTime->getName())) {
            // Modify the shipping method detail to include the delivery time
            $shippingMethodDetail = sprintf('%s (%s)', $shippingMethodDetail, $shippingDeliveryTime->getName());
        }

        $shippingMethod = new AppleShippingOptionStruct();
        $shippingMethod->setIdentifier($shippingMethodEntity->getId());
        $shippingMethod->setLabel($shippingMethodEntity->getName());
        $shippingMethod->setAmount($shippingCostsPrice);
        $shippingMethod->setDetail($shippingMethodDetail);

        return $shippingMethod;
    }

    /**
     * Return shipping payload with all necessary information for Apple Pay
     *
     * @see https://developer.apple.com/documentation/apple_pay_on_the_web/applepaysession/1778008-completeshippingcontactselection
     */
    public function getDirectShippingPayload(
        ?AbstractShippingOptionCollection $shippingMethods,
        DirectPayCartStruct $directPayCart,
        SalesChannelContext $context
    ): AppleShippingPayloadStruct {
        $shippingPayLoad = new AppleShippingPayloadStruct();

        /*
         * SHIPPING METHODS
         */
        if ($shippingMethods instanceof AbstractShippingOptionCollection) {
            $shippingPayLoad->setNewShippingMethods($shippingMethods);
        }

        /*
         * NEW TOTAL
         */
        $shippingPayLoad->setNewTotal(new ApplePayLineItemStruct(
            $this->getShopName($context),
            $directPayCart->getTotalAmount(),
            self::LINE_ITEM_TYPE
        ));

        /**
         * NEW LINE ITEMS
         */
        $lineItems = new ApplePayLineItemCollection();

        // Sub total
        $lineItems->add(new ApplePayLineItemStruct(
            $this->translator->trans('checkoutCom.payments.subtotalLabel'),
            $directPayCart->getLineItemAmount(),
            self::LINE_ITEM_TYPE
        ));

        // Shipping
        foreach ($directPayCart->getShipping() as $shipping) {
            $lineItems->add(new ApplePayLineItemStruct(
                $shipping->getName(),
                $shipping->getPrice(),
                self::LINE_ITEM_TYPE
            ));
        }

        // Taxes
        $directCartTax = $directPayCart->getTax();
        if ($directCartTax instanceof DirectPayCartItemStruct) {
            $lineItems->add(new ApplePayLineItemStruct(
                $this->translator->trans('checkoutCom.payments.taxesLabel'),
                $directCartTax->getPrice(),
                self::LINE_ITEM_TYPE
            ));
        }

        $shippingPayLoad->setNewLineItems($lineItems);

        return $shippingPayLoad;
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SettingStruct $settings,
        SalesChannelContext $context
    ): PaymentRequest {
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

        $walletTokenRequest = new ApplePayTokenRequest();
        $walletTokenRequest->token_data = $this->getTokenData($tokenRequest[self::REQUEST_PAYMENT_DATA]);

        // Call the Checkout.com API to get the token
        $checkoutToken = $this->checkoutTokenService->requestWalletToken($walletTokenRequest, $context->getSalesChannelId());

        return $checkoutToken->getToken();
    }

    private function getValidationDefinition(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.payment_handler.apple_pay');

        // This paymentData is the array of data returned from the browser's Response.
        $definition->add(self::REQUEST_PAYMENT_DATA, new Collection([
            'fields' => [
                'data' => [new Type('string'), new NotBlank()],
                'header' => [new Type('array')],
                'signature' => [new Type('string'), new NotBlank()],
                'version' => [new Type('string'), new NotBlank()],
            ],
            'allowExtraFields' => true,
            'allowMissingFields' => false,
        ]));

        return $definition;
    }

    /**
     * Get Apple Pay token data from payment data of Apple Pay request
     * It always has the correct keys (data, header, signature, version)
     * Because we've already validated it
     */
    private function getTokenData(array $requestTokenData): ApplePayTokenData
    {
        $tokenData = new ApplePayTokenData();
        $tokenData->data = $requestTokenData['data'];
        $tokenData->header = $requestTokenData['header'];
        $tokenData->signature = $requestTokenData['signature'];
        $tokenData->version = $requestTokenData['version'];

        return $tokenData;
    }
}
