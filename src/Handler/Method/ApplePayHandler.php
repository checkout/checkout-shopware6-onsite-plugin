<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestTokenSource;
use Checkout\Tokens\ApplePayTokenData;
use Checkout\Tokens\ApplePayTokenRequest;
use Checkout\Tokens\TokenType;
use CheckoutCom\Shopware6\Exception\CheckoutInvalidTokenException;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class ApplePayHandler extends PaymentHandler
{
    public const REQUEST_PAYMENT_DATA = 'paymentData';

    public static function getPaymentMethodType(): string
    {
        return TokenType::$applepay;
    }

    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.applePayLabel';
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
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

    private function getValidationDefinition(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.payment_handler.apple_pay');

        // This paymentData is the array of data returned from Apple Pay.
        $definition->add(self::REQUEST_PAYMENT_DATA, new Collection([
            'fields' => [
                'data' => [
                    new Type('string'),
                    new NotBlank(),
                ],
                'header' => [
                    new Type('array'),
                ],
                'signature' => [
                    new Type('string'),
                    new NotBlank(),
                ],
                'version' => [
                    new Type('string'),
                    new NotBlank(),
                ],
            ],
            'allowExtraFields' => true,
            'allowMissingFields' => false,
        ]));

        return $definition;
    }
}
