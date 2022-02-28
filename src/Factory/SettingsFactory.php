<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Factory;

use CheckoutcomShopware\Struct\SettingStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class SettingsFactory
{
    public const SYSTEM_CONFIG_DOMAIN = 'CheckoutCom.config.';

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
        $systemConfigData = $this->systemConfigService->getDomain(self::SYSTEM_CONFIG_DOMAIN, $salesChannelId, true);

        foreach ($systemConfigData as $key => $value) {
            if (stripos($key, self::SYSTEM_CONFIG_DOMAIN) === false) {
                continue;
            }

            // We only add the keys that are included in the domain.
            $structData[substr($key, \strlen(self::SYSTEM_CONFIG_DOMAIN))] = $value;
        }

        return (new SettingStruct())->assign($structData);
    }
}
