<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Factory;

use CheckoutCom\Shopware6\Handler\Method\ApplePayHandler;
use CheckoutCom\Shopware6\Handler\Method\GooglePayHandler;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Webhook;
use CheckoutCom\Shopware6\Struct\SystemConfig\ApplePaySettingStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\GooglePaySettingStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class SettingsFactory
{
    public const SYSTEM_CONFIG_DOMAIN = 'CheckoutCom.config.';
    public const CHECKOUT_PLUGIN_CONFIG_SECTION = 'checkoutPluginConfigSectionApi';
    public const CHECKOUT_PLUGIN_CONFIG_SECTION_ORDER_STATE = 'checkoutPluginConfigSectionOrderState';
    public const CHECKOUT_PLUGIN_CONFIG_WEBHOOK = 'checkoutPluginConfigWebhook';

    public const SYSTEM_COMPONENT_GROUP = [
        self::CHECKOUT_PLUGIN_CONFIG_SECTION,
        self::CHECKOUT_PLUGIN_CONFIG_SECTION_ORDER_STATE,
    ];

    public const SYSTEM_COMPONENT_PAYMENT_METHOD = 'paymentMethod';

    private ?SettingStruct $settings = null;

    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * Get Checkout settings from configuration.
     */
    public function getSettings(?string $salesChannelId = null): SettingStruct
    {
        if ($this->settings instanceof SettingStruct) {
            return $this->settings;
        }

        $structData = [];
        $systemConfigData = $this->systemConfigService->getDomain(self::SYSTEM_CONFIG_DOMAIN, $salesChannelId, true);

        foreach ($systemConfigData as $key => $value) {
            if (stripos($key, self::SYSTEM_CONFIG_DOMAIN) === false) {
                continue;
            }

            // We only add the keys that are included in the domain.
            $configKey = substr($key, \strlen(self::SYSTEM_CONFIG_DOMAIN));

            // If the key is the payment method configuration, and its value is an array,
            // we format that value and merge it into the struct.
            if (stripos($configKey, self::SYSTEM_COMPONENT_PAYMENT_METHOD) !== false && \is_array($value)) {
                $structData = array_merge($structData, $this->getPaymentMethodSettings($value));

                continue;
            }

            // If the config key is in the component group & it is an array, we merge it to the struct.
            // otherwise, we just add it to the struct.
            if (\in_array($configKey, self::SYSTEM_COMPONENT_GROUP, true) && \is_array($value)) {
                $structData = array_merge($structData, $value);
            } else {
                $structData[$configKey] = $value;
            }
        }

        $this->settings = (new SettingStruct())->assign($structData);

        return $this->settings;
    }

    public function getWebhookConfig(?string $salesChannelId = null): Webhook
    {
        $config = $this->systemConfigService->get(self::SYSTEM_CONFIG_DOMAIN . self::CHECKOUT_PLUGIN_CONFIG_WEBHOOK, $salesChannelId);

        $webhook = new Webhook();

        if (\is_array($config)) {
            $webhook->assign($config);
        }

        return $webhook;
    }

    /**
     * @param array|bool|float|int|string|null $value
     */
    public function set(string $key, $value, ?string $salesChannelId = null): void
    {
        $this->systemConfigService->set($key, $value, $salesChannelId);
    }

    private function getPaymentMethodSettings(array $paymentMethodConfigs): array
    {
        $paymentMethodStructs = [
            ApplePayHandler::getPaymentMethodType() => ApplePaySettingStruct::class,
            GooglePayHandler::getPaymentMethodType() => GooglePaySettingStruct::class,
        ];

        foreach ($paymentMethodConfigs as $paymentMethodType => $paymentMethodConfig) {
            if (!\array_key_exists($paymentMethodType, $paymentMethodStructs)) {
                continue;
            }

            /** @var Struct $paymentMethodStruct */
            $paymentMethodStruct = new $paymentMethodStructs[$paymentMethodType]();
            $paymentMethodStruct->assign($paymentMethodConfig);
            $paymentMethodConfigs[$paymentMethodType] = $paymentMethodStruct;
        }

        return $paymentMethodConfigs;
    }
}
