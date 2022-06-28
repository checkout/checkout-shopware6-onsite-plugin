<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\CreditCard;

use Checkout\CheckoutApiException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutTokenService;
use CheckoutCom\Shopware6\Service\CreditCard\CreditCardService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Token;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;

class CreditCardServiceTest extends TestCase
{
    use ContextTrait;

    /**
     * @var SettingsFactory|MockObject
     */
    protected $settingsFactory;

    /**
     * @var CheckoutTokenService|MockObject
     */
    protected $checkoutTokenService;

    protected CreditCardService $creditCardService;

    protected $saleChannelContext;

    public function setUp(): void
    {
        $this->settingsFactory = $this->createMock(SettingsFactory::class);
        $this->checkoutTokenService = $this->createMock(CheckoutTokenService::class);
        $this->saleChannelContext = $this->getSaleChannelContext($this);

        $this->creditCardService = new CreditCardService(
            $this->createMock(LoggerService::class),
            $this->settingsFactory,
            $this->checkoutTokenService
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->creditCardService->getDecorated();
    }

    /**
     * @dataProvider createTokenProvider
     */
    public function testCreateToken(
        ?array $errorDetails,
        bool $throwValidateException
    ): void {
        $exception = null;
        if ($errorDetails !== null) {
            $exception = new CheckoutApiException('foo');
            $exception->error_details = $errorDetails;
        }

        $token = new Token();
        if ($exception instanceof CheckoutApiException) {
            $this->checkoutTokenService->expects(static::once())
                ->method('requestCardToken')
                ->willThrowException($exception);

            static::expectException($throwValidateException ? ConstraintViolationException::class : CheckoutApiException::class);
        } else {
            $this->checkoutTokenService->expects(static::once())
                ->method('requestCardToken')
                ->willReturn($token);
        }

        $data = new RequestDataBag([
            'name' => 'Foo',
            'number' => '123456',
            'expiryMonth' => 123,
            'expiryYear' => 123,
            'cvv' => '111',
        ]);
        $expect = $this->creditCardService->createToken($data, $this->saleChannelContext);

        static::assertInstanceOf(Token::class, $expect);
        static::assertSame($expect, $token);
    }

    public function createTokenProvider(): array
    {
        return [
            'Test error_details empty must throw exception' => [
                [],
                false,
            ],
            'Test error_codes empty must throw exception' => [
                [
                    'error_codes' => [],
                ],
                false,
            ],
            'Test error_codes is not array must throw exception' => [
                [
                    'error_codes' => 'string',
                ],
                false,
            ],
            'Test error_codes has data must throw validate exception' => [
                [
                    'error_codes' => [
                        'cvv_invalid',
                    ],
                ],
                true,
            ],
            'Test call api success' => [
                null,
                false,
            ],
        ];
    }
}
