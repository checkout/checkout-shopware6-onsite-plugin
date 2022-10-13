<?php declare(strict_types=1);

namespace Cko\Shopware6\Subscriber;

use Cko\Shopware6\Factory\SettingsFactory;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSettingsGenericPageSubscriber implements EventSubscriberInterface
{
    public const GENERIC_PAGE_EXTENSION = 'checkoutCom';

    private SettingsFactory $settingFactory;

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
        $publicConfigStruct = $this->settingFactory->getPublicConfig(
            $event->getSalesChannelContext()->getSalesChannel()->getId()
        );

        $event->getPage()->addExtension(self::GENERIC_PAGE_EXTENSION, $publicConfigStruct);
    }
}
