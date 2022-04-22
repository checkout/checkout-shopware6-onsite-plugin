<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Factory;

use Checkout\CheckoutApi;
use Checkout\CheckoutDefaultSdk;
use Checkout\Environment;
use CheckoutCom\Shopware6\Struct\SettingStruct;

class CheckoutApiFactory
{
    private SettingsFactory $settingFactory;

    public function __construct(SettingsFactory $settingFactory)
    {
        $this->settingFactory = $settingFactory;
    }

    /**
     * Returns a new instance of the Checkout API client.
     */
    public function getClient(?string $salesChannelId = null): CheckoutApi
    {
        $settings = $this->settingFactory->getSettings($salesChannelId);

        return $this->buildClient($settings);
    }

    /**
     * Build checkout client
     */
    private function buildClient(SettingStruct $settings): CheckoutApi
    {
        $builder = CheckoutDefaultSdk::staticKeys();
        $builder->setEnvironment($settings->isSandboxMode() ? Environment::sandbox() : Environment::production());
        $builder->setSecretKey($settings->getSecretKey());
        $builder->setPublicKey($settings->getPublicKey());

        return $builder->build();
    }
}
