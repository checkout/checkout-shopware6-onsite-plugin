<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Subscriber;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Helper\Url;
use CheckoutCom\Shopware6\Struct\Extension\ConfirmPageExtensionStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSettingsGenericPageSubscriber implements EventSubscriberInterface
{
    public const GENERIC_PAGE_EXTENSION = 'checkoutCom';

    private SettingsFactory $settingFactory;

    private SettingStruct $settings;

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
        $this->settings = $this->settingFactory->getSettings($event->getSalesChannelContext()->getSalesChannel()->getId());
        $this->addCheckoutSettingsToGenericPage($event);
    }

    private function addCheckoutSettingsToGenericPage(GenericPageLoadedEvent $event): void
    {
        $googlePaySettings = $this->settings->getGooglePay();
        $checkoutConfirmPageExtension = new ConfirmPageExtensionStruct();
        $checkoutConfirmPageExtension->setFrameUrl(Url::IFRAME_URL);
        $checkoutConfirmPageExtension->setPublicKey($this->settings->getPublicKey());
        $checkoutConfirmPageExtension->setSandboxMode($this->settings->isSandboxMode());
        $checkoutConfirmPageExtension->setGooglePayMerchantId($googlePaySettings->getMerchantId());

        $event->getPage()->addExtension(self::GENERIC_PAGE_EXTENSION, $checkoutConfirmPageExtension);
    }
}
