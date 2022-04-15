<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestTokenSource;
use Checkout\Tokens\GooglePayTokenData;
use Checkout\Tokens\GooglePayTokenRequest;
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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class GooglePayHandler extends PaymentHandler
{
    public const ENV_TEST = 'TEST';
    public const ENV_PRODUCTION = 'PRODUCTION';

    public static function getPaymentMethodType(): string
    {
        return TokenType::$googlepay;
    }

    public function getSnippetKey(): string
    {
        return 'checkoutCom.paymentMethod.googlePayLabel';
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
