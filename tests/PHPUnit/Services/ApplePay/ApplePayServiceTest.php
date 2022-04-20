<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\ApplePay;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\ApplePay\ApplePayService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\MediaService;
use CheckoutCom\Shopware6\Struct\SettingStruct;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;

class ApplePayServiceTest extends TestCase
{
    use ContextTrait;

    /**
     * @var SettingsFactory|MockObject
     */
    protected $settingFactory;

    /**
     * @var MediaService|MockObject
     */
    protected $mediaService;

    protected ApplePayService $applePayService;

    protected $saleChannelContext;

    public function setUp(): void
    {
        $this->settingFactory = $this->createMock(SettingsFactory::class);
        $this->mediaService = $this->createMock(MediaService::class);
        $this->saleChannelContext = $this->getSaleChannelContext($this);
        $this->applePayService = new ApplePayService(
            $this->createMock(LoggerService::class),
            $this->settingFactory,
            $this->mediaService
        );
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
