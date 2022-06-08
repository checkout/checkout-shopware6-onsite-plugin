<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Subscriber;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Helper\Url;
use CheckoutCom\Shopware6\Struct\Extension\ConfirmPageExtensionStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\GooglePaySettingStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSettingsGenericPageSubscriber implements EventSubscriberInterface
{
    public const GENERIC_PAGE_EXTENSION = 'checkoutCom';

    private SettingsFactory $settingFactory;

    private SettingStruct $settings;

    private GooglePaySettingStruct $googleSettings;

    public function __construct(SettingsFactory $settingFactory)
    {
        $this->settingFactory = $settingFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GenericPageLoadedEvent::class => 'onGenericPageLoaded',
        ];
    }

    public function onGenericPageLoaded(GenericPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannel()->getId();
        $this->getGoogleSettings($salesChannelId);
        $this->settings = $this->settingFactory->getSettings($salesChannelId);

        $this->addCheckoutSettingsToGenericPage($event);
    }

    private function getGoogleSettings(string $salesChannelId): void
    {
        $googleSettings = $this->settingFactory->getPaymentMethodSettings(
            GooglePaySettingStruct::class,
            $salesChannelId
        );

        if (!$googleSettings instanceof GooglePaySettingStruct) {
            throw new CheckoutComException('Google Pay settings not found');
        }

        $this->googleSettings = $googleSettings;
    }

    private function addCheckoutSettingsToGenericPage(GenericPageLoadedEvent $event): void
    {
        $checkoutConfirmPageExtension = new ConfirmPageExtensionStruct();
        $checkoutConfirmPageExtension->setFrameUrl(Url::IFRAME_URL);
        $checkoutConfirmPageExtension->setPublicKey($this->settings->getPublicKey());
        $checkoutConfirmPageExtension->setSandboxMode($this->settings->isSandboxMode());
        $checkoutConfirmPageExtension->setGooglePayMerchantId($this->googleSettings->getMerchantId());

        $event->getPage()->addExtension(self::GENERIC_PAGE_EXTENSION, $checkoutConfirmPageExtension);
    }
}
