<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\ApplePay;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\ApplePay\ApplePayService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\MediaService;
use CheckoutCom\Shopware6\Struct\SettingStruct;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;

class ApplePayServiceTest extends TestCase
{
    use ContextTrait;

    /**
     * @var SystemConfigService|MockObject
     */
    protected $systemConfigService;

    /**
     * @var SettingsFactory|MockObject
     */
    protected $settingFactory;

    /**
     * @var MediaService|MockObject
     */
    protected $mediaService;

    /**
     * @var Client|MockObject
     */
    protected $guzzleClient;

    protected ApplePayService $applePayService;

    protected $saleChannelContext;

    public function setUp(): void
    {
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->settingFactory = $this->createMock(SettingsFactory::class);
        $this->mediaService = $this->createMock(MediaService::class);
        $this->guzzleClient = $this->createMock(Client::class);
        $this->saleChannelContext = $this->getSaleChannelContext($this);
        $this->applePayService = new ApplePayService(
            $this->createMock(LoggerService::class),
            $this->guzzleClient,
            $this->systemConfigService,
            $this->settingFactory,
            $this->mediaService
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->applePayService->getDecorated();
    }

    /**
     * @dataProvider validateMerchantProvider
     */
    public function testValidateMerchant(bool $throwClientException, bool $throwException, ?array $expected): void
    {
        $request = $this->createConfiguredMock(Request::class, [
            'getHost' => 'foo',
        ]);

        $settings = new SettingStruct();
        $settings->setApplePayDomainMediaId('foo');
        $settings->setApplePayPemMediaId('foo');
        $settings->setApplePayKeyMediaId('foo');

        $this->settingFactory->method('getSettings')->willReturn(
            $settings
        );

        $this->mediaService->method('getMedia')->willReturn(
            $this->createMock(MediaEntity::class)
        );

        if ($throwException) {
            $this->mediaService->method('getPathVideoMedia')->willThrowException(
                new Exception()
            );
        }

        if ($throwClientException) {
            $this->guzzleClient->method('post')->willThrowException(
                new ClientException(
                    '',
                    $this->createMock(RequestInterface::class),
                    new Response()
                )
            );
        } else {
            $this->guzzleClient->method('post')->willReturn(
                new Response(
                    200,
                    [],
                    json_encode($expected)
                )
            );
        }

        $merchantSession = $this->applePayService->validateMerchant('foo', $request, $this->saleChannelContext);
        static::assertSame($expected, $merchantSession);
    }

    /**
     * @dataProvider getAppleMediaProvider
     */
    public function testGetAppleKeyMedia(?string $mediaId): void
    {
        $settings = new SettingStruct();
        $settings->setApplePayKeyMediaId($mediaId);

        $this->settingFactory->method('getSettings')->willReturn(
            $settings
        );
        if ($mediaId === null) {
            static::expectException(CheckoutComException::class);
        }

        $media = $this->applePayService->getAppleKeyMedia($this->saleChannelContext);

        static::assertInstanceOf(MediaEntity::class, $media);
    }

    /**
     * @dataProvider getAppleMediaProvider
     */
    public function testGetAppleDomainMedia(?string $mediaId): void
    {
        $settings = new SettingStruct();
        $settings->setApplePayDomainMediaId($mediaId);

        $this->settingFactory->method('getSettings')->willReturn(
            $settings
        );
        if ($mediaId === null) {
            static::expectException(CheckoutComException::class);
        }

        $media = $this->applePayService->getAppleDomainMedia($this->saleChannelContext);

        static::assertInstanceOf(MediaEntity::class, $media);
    }

    /**
     * @dataProvider getAppleMediaProvider
     */
    public function testGetApplePemMedia(?string $mediaId): void
    {
        $settings = new SettingStruct();
        $settings->setApplePayPemMediaId($mediaId);

        $this->settingFactory->method('getSettings')->willReturn(
            $settings
        );
        if ($mediaId === null) {
            static::expectException(CheckoutComException::class);
        }

        $media = $this->applePayService->getApplePemMedia($this->saleChannelContext);

        static::assertInstanceOf(MediaEntity::class, $media);
    }

    public function validateMerchantProvider(): array
    {
        return [
            'Test call api throw client exception' => [
                true,
                false,
                null,
            ],
            'Test throw exception' => [
                false,
                true,
                null,
            ],
            'Test call api successfully' => [
                false,
                false,
                [
                    'foo' => 'data session',
                ],
            ],
        ];
    }

    public function getAppleMediaProvider(): array
    {
        return [
            'Test null media id expect throw exception' => [
                null,
            ],
            'Test has media id expect return media instance' => [
                'foo',
            ],
        ];
    }
}
