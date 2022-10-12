<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Subscriber;

use Cko\Shopware6\Subscriber\OpenApiPathsSubscriber;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\ApiDefinition\Generator\OpenApi\Event\OpenApiPathsEvent;

class OpenApiPathsSubscriberTest extends TestCase
{
    private OpenApiPathsSubscriber $subscriber;

    public function setUp(): void
    {
        $this->subscriber = new OpenApiPathsSubscriber();
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey(OpenApiPathsEvent::class, OpenApiPathsSubscriber::getSubscribedEvents());
    }

    public function testOnOpenApiPaths(): void
    {
        $event = $this->createMock(OpenApiPathsEvent::class);

        $event->expects(static::once())->method('addPath');

        $this->subscriber->onOpenApiPaths($event);
    }
}
