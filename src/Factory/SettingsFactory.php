<?php declare(strict_types=1);

namespace Cko\Shopware6\Factory;

use Cko\Shopware6\Exception\CheckoutComException;
use Cko\Shopware6\Helper\Url;
use Cko\Shopware6\Struct\CheckoutApi\Webhook;
use Cko\Shopware6\Struct\Extension\PublicConfigStruct;
use Cko\Shopware6\Struct\SystemConfig\AbstractPaymentMethodSettingStruct;
use Cko\Shopware6\Struct\SystemConfig\GooglePaySettingStruct;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;

class SettingsFactory
{
    public const SYSTEM_CONFIG_DOMAIN = 'CkoShopware6.config.';
    public const SYSTEM_COMPONENT_PAYMENT_METHOD = 'paymentMethod';
    public const CHECKOUT_PLUGIN_CONFIG_SECTION = 'checkoutPluginConfigSectionApi';
    public const CHECKOUT_PLUGIN_CONFIG_WEBHOOK = 'checkoutPluginConfigWebhook';
    public const CHECKOUT_PLUGIN_CONFIG_3DS = 'enable3dSecure';

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

    public function get3dSecureConfig(?string $salesChannelId = null): bool
    {
        return $this->systemConfigService->getBool(self::SYSTEM_CONFIG_DOMAIN . self::CHECKOUT_PLUGIN_CONFIG_3DS, $salesChannelId);
    }

    /**
     * Get public config of the plugin
     */
    public function getPublicConfig(string $salesChannelId): PublicConfigStruct
    {
        $settings = $this->getSettings($salesChannelId);
        $googleSettings = $this->getPaymentMethodSettings(
            GooglePaySettingStruct::class,
            $salesChannelId
        );

        if (!$googleSettings instanceof GooglePaySettingStruct) {
            throw new CheckoutComException('Google Pay settings not found');
        }

        $publicConfigStruct = new PublicConfigStruct();
        $publicConfigStruct->setFrameUrl(Url::IFRAME_URL);
        $publicConfigStruct->setKlarnaCdnUrl(Url::KLARNA_CDN_URL);
        $publicConfigStruct->setPublicKey($settings->getPublicKey());
        $publicConfigStruct->setSandboxMode($settings->isSandboxMode());
        $publicConfigStruct->setGooglePayMerchantId($googleSettings->getMerchantId());

        return $publicConfigStruct;
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
