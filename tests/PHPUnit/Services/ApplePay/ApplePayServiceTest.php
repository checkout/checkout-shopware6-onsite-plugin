<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\ApplePay;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\ApplePay\ApplePayService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\MediaService;
use CheckoutCom\Shopware6\Struct\SystemConfig\ApplePaySettingStruct;
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
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
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

        $applePaySettings = new ApplePaySettingStruct();
        $applePaySettings->setPemMediaId('foo');
        $applePaySettings->setKeyMediaId('foo');

        $this->settingFactory->method('getPaymentMethodSettings')->willReturn(
            $applePaySettings
        );

        $this->mediaService->method('getMedia')->willReturn(
            $this->createMock(MediaEntity::class)
        );

        if ($throwException) {
            static::expectException(CheckoutComException::class);
            $this->mediaService->method('getPathVideoMedia')->willThrowException(
                new Exception()
            );
        }

        if ($throwClientException) {
            static::expectException(CheckoutComException::class);
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
        $applePaySettings = new ApplePaySettingStruct();
        $applePaySettings->setKeyMediaId($mediaId);

        $this->settingFactory->method('getPaymentMethodSettings')->willReturn(
            $applePaySettings
        );
        if ($mediaId === null) {
            static::expectException(CheckoutComException::class);
        }

        $media = $this->applePayService->getAppleKeyMedia($this->saleChannelContext);

        static::assertInstanceOf(MediaEntity::class, $media);
    }

    /**
     * @dataProvider getAppleDomainMediaProvider
     */
    public function testGetAppleDomainMedia(?array $domainMedias, bool $expectThrow): void
    {
        $salesChannelDomain = $this->createConfiguredMock(SalesChannelDomainEntity::class, [
            'getId' => 'foo',
        ]);
        $applePaySettings = new ApplePaySettingStruct();
        if ($domainMedias !== null) {
            $applePaySettings->setDomainMedias($domainMedias);
        }

        if ($expectThrow) {
            static::expectException(CheckoutComException::class);
        }

        $this->settingFactory->method('getPaymentMethodSettings')->willReturn(
            $applePaySettings
        );

        $media = $this->applePayService->getAppleDomainMedia($salesChannelDomain, $this->saleChannelContext);

        static::assertInstanceOf(MediaEntity::class, $media);
    }

    /**
     * @dataProvider getAppleMediaProvider
     */
    public function testGetApplePemMedia(?string $mediaId): void
    {
        $applePaySettings = new ApplePaySettingStruct();
        $applePaySettings->setPemMediaId($mediaId);

        $this->settingFactory->method('getPaymentMethodSettings')->willReturn(
            $applePaySettings
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

    public function getAppleDomainMediaProvider(): array
    {
        return [
            'Test null media id expect throw exception' => [
                null,
                true,
            ],
            'Test not found domain Id key expect throw exception' => [
                [
                    [
                        'wrong_domain_id' => 'foo',
                        'mediaId' => 'foo',
                    ],
                ],
                true,
            ],
            'Test domain Id null expect throw exception' => [
                [
                    [
                        'domainId' => null,
                        'mediaId' => 'foo',
                    ],
                ],
                true,
            ],
            'Test not found media Id key expect throw exception' => [
                [
                    [
                        'domainId' => 'foo',
                        'wrong_media_id' => 'foo',
                    ],
                ],
                true,
            ],
            'Test mediaId null expect throw exception' => [
                [
                    [
                        'domainId' => 'foo',
                        'mediaId' => null,
                    ],
                ],
                true,
            ],
            'Test has media id expect return media instance' => [
                [
                    [
                        'domainId' => 'foo',
                        'mediaId' => 'foo',
                    ],
                ],
                false,
            ],
        ];
    }
}
