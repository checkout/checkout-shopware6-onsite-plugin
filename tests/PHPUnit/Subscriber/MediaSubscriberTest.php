<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Subscriber;

use CheckoutCom\Shopware6\Subscriber\MediaSubscriber;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\Event\MediaFileExtensionWhitelistEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class MediaSubscriberTest extends TestCase
{
    use ContextTrait;

    private SalesChannelContext $salesChannelContext;

    private MediaSubscriber $subscriber;

    public function setUp(): void
    {
        $this->subscriber = new MediaSubscriber();
        $this->salesChannelContext = $this->getSaleChannelContext($this);
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey(MediaFileExtensionWhitelistEvent::class, MediaSubscriber::getSubscribedEvents());
    }

    public function testOnMediaFileExtensionWhitelist(): void
    {
        $event = new MediaFileExtensionWhitelistEvent([
            'any',
            'old',
        ]);

        $this->subscriber->onMediaFileExtensionWhitelist($event);

        static::assertContains('key', $event->getWhitelist());
        static::assertContains('pem', $event->getWhitelist());
    }
}
