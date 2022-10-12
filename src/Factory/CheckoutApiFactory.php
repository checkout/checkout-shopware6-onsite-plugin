<?php declare(strict_types=1);

namespace Cko\Shopware6\Factory;

use Checkout\CheckoutApi;
use Checkout\CheckoutSdkBuilder;
use Checkout\Environment;
use Checkout\Previous\CheckoutApi as CheckoutPreviousApi;
use Cko\Shopware6\Service\CheckoutApi\CheckoutStaticKeysSdkBuilder;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;

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
     * Returns a new instance of the Checkout API client.
     */
    public function getPreviousClient(?string $salesChannelId = null): CheckoutPreviousApi
    {
        $settings = $this->settingFactory->getSettings($salesChannelId);

        return $this->buildPreviousClient($settings);
    }

    /**
     * Build checkout client
     */
    private function buildClient(SettingStruct $settings): CheckoutApi
    {
        $builder = (new CheckoutSdkBuilder())->staticKeys();
        $builder->environment($settings->isSandboxMode() ? Environment::sandbox() : Environment::production());
        $builder->secretKey($settings->getSecretKey());
        $builder->publicKey($settings->getPublicKey());

        return $builder->build();
    }

    /**
     * Build checkout previous client
     */
    private function buildPreviousClient(SettingStruct $settings): CheckoutPreviousApi
    {
        $builder = new CheckoutStaticKeysSdkBuilder();
        $builder->environment($settings->isSandboxMode() ? Environment::sandbox() : Environment::production());
        $builder->secretKey($this->formatKeyBySetting($settings, $settings->getSecretKey()));
        $builder->publicKey($this->formatKeyBySetting($settings, $settings->getPublicKey()));

        return $builder->build();
    }

    private function formatKeyBySetting(SettingStruct $settings, string $key): string
    {
        if ($settings->isAccountType(SettingStruct::ACCOUNT_TYPE_ABC)) {
            return $key;
        }

        return sprintf('Bearer %s', $key);
    }
}
