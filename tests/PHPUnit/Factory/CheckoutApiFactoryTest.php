<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Factory;

use Checkout\CheckoutApi;
use Checkout\CheckoutArgumentException;
use CheckoutCom\Shopware6\Factory\CheckoutApiFactory;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Struct\SettingStruct;
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
    public function testGetClient(string $publicKey, string $secretKey, ?string $exception): void
    {
        $settings = new SettingStruct();
        $settings->setPublicKey($publicKey);
        $settings->setSecretKey($secretKey);

        if ($exception !== null) {
            static::expectException($exception);
        }

        $this->settingFactory->expects(static::once())->method('getSettings')->willReturn($settings);
        $checkoutApi = $this->checkoutApiFactory->getClient('foo');

        static::assertInstanceOf(CheckoutApi::class, $checkoutApi);
    }

    public function getClientProvider()
    {
        return [
            'Test must throw secret key not set exception' => [
                'publicKey' => 'Test public key',
                'secretKey' => 'test secret key',
                'exception' => CheckoutArgumentException::class,
            ],
            'Test must throw public key not set exception' => [
                'publicKey' => 'test',
                'secretKey' => 'sk_test_13ef31b4-5a22-34f2-a583-bd2a32a43383',
                'exception' => CheckoutArgumentException::class,
            ],
            'Test get client successful' => [
                'publicKey' => 'pk_test_231b7f5d-3fva-44bv-9fce-572f323da16g',
                'secretKey' => 'sk_test_13ef31b4-5a22-34f2-a583-bd2a32a43383',
                'exception' => null,
            ],
        ];
    }
}
