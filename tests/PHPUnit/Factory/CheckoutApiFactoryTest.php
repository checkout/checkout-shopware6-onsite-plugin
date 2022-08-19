<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Factory;

use Checkout\Previous\CheckoutApi;
use CheckoutCom\Shopware6\Factory\CheckoutApiFactory;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutApiFactoryTest extends TestCase
{
    /**
     * @var MockObject|SettingsFactory
     */
    private $settingFactory;

    private CheckoutApiFactory $checkoutApiFactory;

    public function setUp(): void
    {
        $this->settingFactory = $this->createMock(SettingsFactory::class);
        $this->checkoutApiFactory = new CheckoutApiFactory($this->settingFactory);
    }

    /**
     * @dataProvider getClientProvider
     */
    public function testGetClient(string $publicKey, string $secretKey): void
    {
        $settings = new SettingStruct();
        $settings->setPublicKey($publicKey);
        $settings->setSecretKey($secretKey);

        $this->settingFactory->expects(static::once())->method('getSettings')->willReturn($settings);
        $checkoutApi = $this->checkoutApiFactory->getPreviousClient('foo');

        static::assertInstanceOf(CheckoutApi::class, $checkoutApi);
    }

    public function getClientProvider(): array
    {
        return [
            'Test get client successful' => [
                'publicKey' => 'pk_test_231b7f5d-3fva-44bv-9fce-572f323da16g',
                'secretKey' => 'sk_test_13ef31b4-5a22-34f2-a583-bd2a32a43383',
            ],
        ];
    }
}
