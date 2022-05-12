<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Factory;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Struct\SettingStruct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class SettingsFactoryTest extends TestCase
{
    /**
     * @var MockObject|SystemConfigService
     */
    private $systemConfigService;

    private SettingsFactory $settingsFactory;

    public function setUp(): void
    {
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->settingsFactory = new SettingsFactory($this->systemConfigService);
    }

    /**
     * @dataProvider getSettingsProvider
     */
    public function testGetSettings(array $systemConfigData): void
    {
        $saleChannelId = 'test';
        $this->systemConfigService->method('getDomain')
            ->willReturn($systemConfigData);

        $settings = $this->settingsFactory->getSettings($saleChannelId);

        static::assertInstanceOf(SettingStruct::class, $settings);
    }

    public function getSettingsProvider(): array
    {
        return [
            'Test empty setting config must return settings struct instance' => [
                [],
            ],
            'Test wrong domain still return settings struct instance' => [
                ['wrong Domain' => 'test value'],
            ],
            'Test data add success with current value' => [
                [
                    SettingsFactory::SYSTEM_CONFIG_DOMAIN . 'anyKey' => 'test value',
                ],
            ],
            'Test data add success without merge value cause it is not array' => [
                [
                    SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::SYSTEM_COMPONENT_GROUP[0] => 'Test value',
                ],
            ],
            'Test data add success with merge value' => [
                [
                    SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::SYSTEM_COMPONENT_GROUP[0] => [
                        'test key' => 'test value',
                    ],
                ],
            ],
        ];
    }

    public function testSetFunction(): void
    {
        $this->systemConfigService->expects(static::once())->method('set');

        $this->settingsFactory->set('test', 'test');
    }

    public function testGetWebhookConfig(): void
    {
        $result = ['id' => 'test'];
        $this->systemConfigService->expects(static::once())->method('get')->willReturn($result);

        $webhook = $this->settingsFactory->getWebhookConfig();

        static::assertSame($result['id'], $webhook->getId());
    }
}
