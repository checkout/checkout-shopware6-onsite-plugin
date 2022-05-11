<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Subscriber;

use CheckoutCom\Shopware6\Exception\CheckoutComWebhookNotFoundException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Webhook;
use CheckoutCom\Shopware6\Subscriber\SystemConfigSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\Event\BeforeSystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;

class SystemConfigSubscriberTest extends TestCase
{
    /**
     * @var CheckoutWebhookService|MockObject
     */
    private $checkoutWebhookService;

    /**
     * @var SettingsFactory|MockObject
     */
    private $settingsFactory;

    /**
     * @var SystemConfigSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->checkoutWebhookService = $this->createMock(CheckoutWebhookService::class);
        $this->settingsFactory = $this->createMock(SettingsFactory::class);

        $this->subscriber = new SystemConfigSubscriber($this->checkoutWebhookService, $this->settingsFactory);
    }

    public function testSubscriber(): void
    {
        static::assertSame(
            [
                SystemConfigChangedEvent::class => 'onChangeConfig',
                BeforeSystemConfigChangedEvent::class => 'onBeforeChangeConfig',
            ],
            SystemConfigSubscriber::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider getDataBeforeChange
     */
    public function testOnBeforeChangeConfig($key, $value): void
    {
        $event = new BeforeSystemConfigChangedEvent($key, $value, null);
        $this->subscriber->onBeforeChangeConfig($event);

        static::assertSame($value, $event->getValue());
    }

    public function getDataBeforeChange(): array
    {
        return [
            'invalid key' => ['test', []],
            'valid key' => [
                SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_WEBHOOK,
                ['test'],
            ],
        ];
    }

    /**
     * @dataProvider getDataChanged
     */
    public function testOnChangeConfig($key, Webhook $webhook): void
    {
        $event = new SystemConfigChangedEvent($key, [], null);

        if ($event->getKey() !== SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_SECTION) {
            $this->settingsFactory->expects(static::never())->method('getWebhookConfig');
        } else {
            $this->settingsFactory->expects(static::once())->method('getWebhookConfig')->willReturn($webhook);
            $this->settingsFactory->expects(static::once())->method('set');
            $this->checkoutWebhookService->expects(static::once())->method('registerWebhook')->willReturn($webhook);
        }

        $this->subscriber->onChangeConfig($event);
    }

    public function getDataChanged(): array
    {
        return [
            'invalid key' => ['test', new Webhook()],
            'valid key, webhook id null' => [
                SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_SECTION,
                new Webhook(),
            ],
        ];
    }

    public function testOnChangeConfigWithExistsWebhook(): void
    {
        $event = new SystemConfigChangedEvent(
            SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_SECTION,
            [],
            null
        );

        $webhook = new Webhook();
        $webhook->setId('test');
        $this->settingsFactory->expects(static::once())->method('getWebhookConfig')->willReturn($webhook);
        $this->checkoutWebhookService->expects(static::once())->method('retrieveWebhook')->willReturn($webhook);

        $this->subscriber->onChangeConfig($event);
    }

    public function testOnChangeConfigWithException(): void
    {
        $event = new SystemConfigChangedEvent(
            SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_SECTION,
            [],
            null
        );

        $webhook = new Webhook();
        $webhook->setId('test');
        $this->settingsFactory->expects(static::once())->method('getWebhookConfig')->willReturn($webhook);
        $this->checkoutWebhookService->expects(static::once())->method('retrieveWebhook')->willThrowException(new CheckoutComWebhookNotFoundException('test'));
        $this->settingsFactory->expects(static::once())->method('set');
        $this->checkoutWebhookService->expects(static::once())->method('registerWebhook')->willReturn($webhook);

        $this->subscriber->onChangeConfig($event);
    }
}
