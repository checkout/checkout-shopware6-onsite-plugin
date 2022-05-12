<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Subscriber;

use Checkout\CheckoutApiException;
use CheckoutCom\Shopware6\Exception\CheckoutComWebhookNotFoundException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use Shopware\Core\System\SystemConfig\Event\BeforeSystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber is used to register the webhook to Checkout whenever
 * CheckoutCom.config.checkoutPluginConfigSectionApi config key is updated
 */
class SystemConfigSubscriber implements EventSubscriberInterface
{
    private array $webhookData = [];

    private CheckoutWebhookService $checkoutWebhookService;

    private SettingsFactory $settingsFactory;

    public function __construct(
        CheckoutWebhookService $checkoutWebhookService,
        SettingsFactory $settingsFactory
    ) {
        $this->checkoutWebhookService = $checkoutWebhookService;
        $this->settingsFactory = $settingsFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onChangeConfig',
            BeforeSystemConfigChangedEvent::class => 'onBeforeChangeConfig',
        ];
    }

    /**
     * To ensure the webhook data won't be replaced by the old value
     */
    public function onBeforeChangeConfig(BeforeSystemConfigChangedEvent $event): void
    {
        if ($event->getKey() !== SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_WEBHOOK) {
            return;
        }

        if (empty($this->webhookData)) {
            return;
        }

        $event->setValue($this->webhookData);
    }

    /**
     * Register webhook to Checkout after updated plugin config
     */
    public function onChangeConfig(SystemConfigChangedEvent $event): void
    {
        if ($event->getKey() !== SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_SECTION) {
            return;
        }

        $salesChannelId = $event->getSalesChannelId();
        $webhook = $this->settingsFactory->getWebhookConfig($salesChannelId);

        $webhookId = $webhook->getId();
        // Registering new webhook if there is no registered webhook
        if ($webhookId === null) {
            $this->registerWebhook($salesChannelId);

            return;
        }

        try {
            // skip if webhook already registered
            $this->checkoutWebhookService->retrieveWebhook($webhookId, $salesChannelId);

            return;
        } catch (CheckoutComWebhookNotFoundException $e) {
            // Register a new webhook when "retrieveWebhook" returns a 404 status
            $this->registerWebhook($salesChannelId);
        } catch (CheckoutApiException $e) {
            // do nothing to make sure this exception does not block any action behind
        }
    }

    private function registerWebhook(?string $salesChannelId = null): void
    {
        try {
            $webhook = $this->checkoutWebhookService->registerWebhook($salesChannelId);

            // save registered webhook data to system config
            $this->settingsFactory->set(
                SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_WEBHOOK,
                $webhook->getVars(),
                $salesChannelId
            );

            $this->webhookData = $webhook->getVars();
        } catch (CheckoutApiException $e) {
            // do nothing to make sure this exception does not block any action behind
        }
    }
}
