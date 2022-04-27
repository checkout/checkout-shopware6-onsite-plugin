<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CheckoutApi;

use Checkout\Tokens\ApplePayTokenRequest;
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
    public function testRequestPayment(bool $expectThrowException): void
    {
        $this->handleTestCheckoutRequest($expectThrowException, 'post');

        $walletTokenRequest = new ApplePayTokenRequest();
        $token = $this->checkoutTokenService->requestWalletToken(
            $walletTokenRequest,
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
