<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\CheckoutApi;

use Checkout\CheckoutApiException;
use Checkout\Tokens\WalletTokenRequest;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Token;

class CheckoutTokenService extends AbstractCheckoutService
{
    /**
     * @throws CheckoutApiException
     */
    public function requestWalletToken(WalletTokenRequest $walletTokenRequest, string $salesChannelId): Token
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $tokenResponse = $checkoutApi->getTokensClient()->requestWalletToken($walletTokenRequest);

            return (new Token())->assign($tokenResponse);
        } catch (CheckoutApiException $e) {
            $errorMessage = $this->modifyAndLogMessage($e, __FUNCTION__);

            throw new CheckoutApiException($errorMessage);
        }
    }
}
