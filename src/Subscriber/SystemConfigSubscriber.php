<?php
declare(strict_types=1);

namespace Cko\Shopware6\Subscriber;

use Checkout\CheckoutApiException;
use Checkout\HttpMetadata;
use Cko\Shopware6\Exception\CheckoutComWebhookNotFoundException;
use Cko\Shopware6\Factory\SettingsFactory;
use Cko\Shopware6\Service\CheckoutApi\CheckoutWebhookService;
use Cko\Shopware6\Service\CheckoutApi\CheckoutWorkflowService;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\System\SystemConfig\Event\BeforeSystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * This subscriber is used to register the webhook to Checkout.com whenever
 * CkoShopware6.config.checkoutPluginConfigSectionApi config key is updated
 */
class SystemConfigSubscriber implements EventSubscriberInterface
{
    private array $webhookData = [];

    private CheckoutWebhookService $checkoutWebhookService;

    private CheckoutWorkflowService $checkoutWorkflowService;

    private SettingsFactory $settingsFactory;

    public function __construct(
        CheckoutWebhookService $checkoutWebhookService,
        CheckoutWorkflowService $checkoutWorkflowService,
        SettingsFactory $settingsFactory
    ) {
        $this->checkoutWebhookService = $checkoutWebhookService;
        $this->checkoutWorkflowService = $checkoutWorkflowService;
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
     * Register webhook to checkout.com after updated plugin config
     */
    public function onChangeConfig(SystemConfigChangedEvent $event): void
    {
        if ($event->getKey() !== SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_SECTION) {
            return;
        }

        $salesChannelId = $event->getSalesChannelId();
        if ($event->getValue() === null) {
            $this->setSettingWebhook(null, $salesChannelId);

            return;
        }

        $settings = $this->settingsFactory->getSettings($salesChannelId);
        $webhook = $this->settingsFactory->getWebhookConfig($salesChannelId);

        $webhookId = $webhook->getId();
        // Registering new webhook if there is no registered webhook
        if ($webhookId === null) {
            $this->registerWebhook($settings, $salesChannelId);

            return;
        }

        try {
            // skip if webhook already registered
            $this->receiveWebhook($settings, $webhookId, $salesChannelId);
        } catch (CheckoutComWebhookNotFoundException $e) {
            // Register a new webhook when "retrieveWebhook" returns a 404 status
            $this->registerWebhook($settings, $salesChannelId);
        } catch (Throwable $e) {
            // do nothing to make sure this exception does not block any action behind
        }
    }

    private function setSettingWebhook(?array $webhookData = null, ?string $salesChannelId = null): void
    {
        $this->settingsFactory->set(
            SettingsFactory::SYSTEM_CONFIG_DOMAIN . SettingsFactory::CHECKOUT_PLUGIN_CONFIG_WEBHOOK,
            $webhookData,
            $salesChannelId
        );
    }

    private function registerWebhook(SettingStruct $settings, ?string $salesChannelId = null): void
    {
        try {
            if ($settings->isAccountType(SettingStruct::ACCOUNT_TYPE_ABC)) {
                $webhook = $this->checkoutWebhookService->registerWebhook($salesChannelId);
            } else {
                $webhook = $this->checkoutWorkflowService->createWorkFlows($salesChannelId);
            }

            // save registered webhook data to system config
            $this->setSettingWebhook($webhook->getVars(), $salesChannelId);

            $this->webhookData = $webhook->getVars();
        } catch (Throwable $e) {
            // do nothing to make sure this exception does not block any action behind
        }
    }

    /**
     * @throws CheckoutApiException
     */
    private function receiveWebhook(SettingStruct $settings, string $webhookId, ?string $salesChannelId): void
    {
        try {
            if ($settings->isAccountType(SettingStruct::ACCOUNT_TYPE_ABC)) {
                $this->checkoutWebhookService->retrieveWebhook($webhookId, $salesChannelId);
            } else {
                $this->checkoutWorkflowService->getWorkflow($webhookId, $salesChannelId);
            }
        } catch (CheckoutApiException $e) {
            $httpMetaData = $e->http_metadata;

            if (!$httpMetaData instanceof HttpMetadata) {
                throw new CheckoutComWebhookNotFoundException($webhookId);
            }

            if ($httpMetaData->getStatusCode() === Response::HTTP_NOT_FOUND) {
                throw new CheckoutComWebhookNotFoundException($webhookId);
            }

            throw $e;
        }
    }
}
