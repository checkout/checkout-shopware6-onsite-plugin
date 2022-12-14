<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\CheckoutApi;

use Checkout\CheckoutApiException;
use Checkout\Tokens\CardTokenRequest;
use Checkout\Tokens\WalletTokenRequest;
use Cko\Shopware6\Struct\CheckoutApi\Resources\Token;

class CheckoutTokenService extends AbstractCheckoutService
{
    /**
     * @throws CheckoutApiException
     */
    public function requestWalletToken(WalletTokenRequest $walletTokenRequest, string $salesChannelId): Token
    {
        $checkoutApi = $this->checkoutApiFactory->getPreviousClient($salesChannelId);

        try {
            $tokenResponse = $checkoutApi->getTokensClient()->requestWalletToken($walletTokenRequest);

            return (new Token())->assign($tokenResponse);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * @throws CheckoutApiException
     */
    public function requestCardToken(CardTokenRequest $cardTokenRequest, string $salesChannelId): Token
    {
        $checkoutApi = $this->checkoutApiFactory->getPreviousClient($salesChannelId);

        try {
            $tokenResponse = $checkoutApi->getTokensClient()->requestCardToken($cardTokenRequest);

            return (new Token())->assign($tokenResponse);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }
}
