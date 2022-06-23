<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CheckoutApi;

use Checkout\Tokens\ApplePayTokenRequest;
use Checkout\Tokens\CardTokenRequest;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutTokenService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Token;

class CheckoutTokenServiceTest extends AbstractCheckoutTest
{
    protected CheckoutTokenService $checkoutTokenService;

    public function setUp(): void
    {
        parent::setUp();
        $this->checkoutTokenService = new CheckoutTokenService(
            $this->logger,
            $this->getCheckoutApiFactory()
        );
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testRequestWalletToken(bool $expectThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($expectThrowException, 'post');

        $walletTokenRequest = new ApplePayTokenRequest();
        $token = $this->checkoutTokenService->requestWalletToken(
            $walletTokenRequest,
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertInstanceOf(Token::class, $token);
    }

    /**
     * @dataProvider requestCheckoutApiProvider
     */
    public function testRequestCardToken(bool $expectThrowException): void
    {
        $this->handleCheckoutRequestShouldThrowException($expectThrowException, 'post');

        $cardTokenRequest = new CardTokenRequest();
        $token = $this->checkoutTokenService->requestCardToken(
            $cardTokenRequest,
            $this->salesChannelContext->getSalesChannelId()
        );

        static::assertInstanceOf(Token::class, $token);
    }

    public function requestCheckoutApiProvider(): array
    {
        return [
            'Test throw checkout api exception' => [
                true,
            ],
            'Test call api successful' => [
                false,
            ],
        ];
    }
}
