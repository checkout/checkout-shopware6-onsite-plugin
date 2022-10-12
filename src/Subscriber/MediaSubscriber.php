<?php declare(strict_types=1);

namespace Cko\Shopware6\Subscriber;

use Shopware\Core\Content\Media\Event\MediaFileExtensionWhitelistEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MediaFileExtensionWhitelistEvent::class => 'onMediaFileExtensionWhitelist',
        ];
    }

    public function onMediaFileExtensionWhitelist(MediaFileExtensionWhitelistEvent $event): void
    {
        $newWhiteList = array_merge([
            'key',
            'pem',
        ], $event->getWhitelist());

        $event->setWhitelist($newWhiteList);
    }
}
