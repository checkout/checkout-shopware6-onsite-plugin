<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Subscriber;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Helper\Url;
use CheckoutCom\Shopware6\Struct\Extension\ConfirmPageExtensionStruct;
use CheckoutCom\Shopware6\Struct\SettingStruct;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmPageSubscriber implements EventSubscriberInterface
{
    public const CONFIRM_PAGE_EXTENSION = 'checkoutCom';

    private SettingsFactory $settingFactory;

    private SettingStruct $settings;

    public function __construct(SettingsFactory $settingFactory)
    {
        $this->settingFactory = $settingFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
            AccountEditOrderPageLoadedEvent::class => 'onAccountEditOrderPageLoaded',
        ];
    }

    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $this->settings = $this->settingFactory->getSettings($event->getSalesChannelContext()->getSalesChannel()->getId());
        $this->addCheckoutSettingsToConfirmPage($event);
    }

    public function onAccountEditOrderPageLoaded($event): void
    {
        $this->settings = $this->settingFactory->getSettings($event->getSalesChannelContext()->getSalesChannel()->getId());
        $this->addCheckoutSettingsToConfirmPage($event);
    }

    private function addCheckoutSettingsToConfirmPage(PageLoadedEvent $event): void
    {
        $checkoutConfirmPageExtension = new ConfirmPageExtensionStruct();
        $checkoutConfirmPageExtension->setFrameUrl(Url::IFRAME_URL);
        $checkoutConfirmPageExtension->setPublicKey($this->settings->getPublicKey());
        $checkoutConfirmPageExtension->setSandboxMode($this->settings->isSandboxMode());

        $event->getPage()->addExtension(self::CONFIRM_PAGE_EXTENSION, $checkoutConfirmPageExtension);
    }
}
