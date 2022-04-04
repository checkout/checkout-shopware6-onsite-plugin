<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Factory;

use CheckoutCom\Shopware6\Struct\SettingStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class SettingsFactory
{
    public const SYSTEM_CONFIG_DOMAIN = 'CheckoutCom.config.';
    public const SYSTEM_COMPONENT_GROUP = [
        'checkoutPluginConfigSectionApi',
        'checkoutPluginConfigSectionOrderState',
    ];

    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * Get Checkout settings from configuration.
     */
    public function getSettings(string $salesChannelId): SettingStruct
    {
        $structData = [];
        $systemConfigData = $this->systemConfigService->getDomain(self::SYSTEM_CONFIG_DOMAIN, $salesChannelId, true);

        foreach ($systemConfigData as $key => $value) {
            if (stripos($key, self::SYSTEM_CONFIG_DOMAIN) === false) {
                continue;
            }

            // We only add the keys that are included in the domain.
            $configKey = substr($key, \strlen(self::SYSTEM_CONFIG_DOMAIN));

            // If the config key is in the component group & it is an array, we merge it to the struct.
            // otherwise, we just add it to the struct.
            if (\in_array($configKey, self::SYSTEM_COMPONENT_GROUP, true) && \is_array($value)) {
                $structData = array_merge($structData, $value);
            } else {
                $structData[$configKey] = $value;
            }
        }

        return (new SettingStruct())->assign($structData);
    }
}
