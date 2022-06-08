<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Factory;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Webhook;
use CheckoutCom\Shopware6\Struct\SystemConfig\AbstractPaymentMethodSettingStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;

class SettingsFactory
{
    public const SYSTEM_CONFIG_DOMAIN = 'CheckoutCom.config.';
    public const SYSTEM_COMPONENT_PAYMENT_METHOD = 'paymentMethod';
    public const CHECKOUT_PLUGIN_CONFIG_SECTION = 'checkoutPluginConfigSectionApi';
    public const CHECKOUT_PLUGIN_CONFIG_WEBHOOK = 'checkoutPluginConfigWebhook';

    public const SYSTEM_COMPONENT_GROUP = [
        self::CHECKOUT_PLUGIN_CONFIG_SECTION,
    ];

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
        $structData = [];
        $systemConfigData = $this->getSystemConfigSettings($salesChannelId);

        foreach ($systemConfigData as $key => $value) {
            if (stripos($key, self::SYSTEM_CONFIG_DOMAIN) === false) {
                continue;
            }

            // We only add the keys that are included in the domain.
            $configKey = substr($key, \strlen(self::SYSTEM_CONFIG_DOMAIN));

            // If the key is the payment method configuration, skip it
            if (stripos($configKey, self::SYSTEM_COMPONENT_PAYMENT_METHOD) !== false) {
                continue;
            }

            // If the config key is in the component group & it is an array, merge it to the struct.
            // otherwise, just add it to the struct.
            if (\in_array($configKey, self::SYSTEM_COMPONENT_GROUP, true) && \is_array($value)) {
                $structData = array_merge($structData, $value);
            } else {
                $structData[$configKey] = $value;
            }
        }

        return (new SettingStruct())->assign($structData);
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
     * Get Checkout payment method settings from configuration.
     */
    public function getPaymentMethodSettings(string $paymentMethodConfigClassName, ?string $salesChannelId = null): AbstractPaymentMethodSettingStruct
    {
        try {
            $structData = [];
            $systemConfigData = $this->getSystemConfigSettings($salesChannelId);
            $paymentMethodConfigDomain = $this->getPaymentMethodDomain();

            /** @var AbstractPaymentMethodSettingStruct $paymentMethodSystemConfig */
            $paymentMethodSystemConfig = new $paymentMethodConfigClassName();
            $paymentMethodTypeKey = sprintf('%s.', $paymentMethodSystemConfig->getPaymentMethodType());

            foreach ($systemConfigData as $key => $value) {
                if (stripos($key, $paymentMethodConfigDomain) === false) {
                    continue;
                }

                // Get the payment method domain key from the key.
                $configKey = substr($key, \strlen($paymentMethodConfigDomain));
                if (stripos($configKey, $paymentMethodTypeKey) === false) {
                    continue;
                }

                // Get the payment method type key from the payment method domain key.
                $configKey = substr($configKey, \strlen($paymentMethodTypeKey));
                $structData[$configKey] = $value;
            }

            return $paymentMethodSystemConfig->assign($structData);
        } catch (Throwable $e) {
            throw new CheckoutComException($e->getMessage());
        }
    }

    /**
     * @param array|bool|float|int|string|null $value
     */
    public function set(string $key, $value, ?string $salesChannelId = null): void
    {
        $this->systemConfigService->set($key, $value, $salesChannelId);
    }

    private function getSystemConfigSettings(?string $salesChannelId = null): array
    {
        return $this->systemConfigService->getDomain(self::SYSTEM_CONFIG_DOMAIN, $salesChannelId, true);
    }

    private function getPaymentMethodDomain(): string
    {
        return sprintf('%s%s.', self::SYSTEM_CONFIG_DOMAIN, self::SYSTEM_COMPONENT_PAYMENT_METHOD);
    }
}
