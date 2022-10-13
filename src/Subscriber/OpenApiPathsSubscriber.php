<?php declare(strict_types=1);

namespace Cko\Shopware6\Subscriber;

use Shopware\Core\Framework\Api\ApiDefinition\Generator\OpenApi\Event\OpenApiPathsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OpenApiPathsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [OpenApiPathsEvent::class => 'onOpenApiPaths'];
    }

    /**
     * Add register path to swagger
     */
    public function onOpenApiPaths(OpenApiPathsEvent $event): void
    {
        $event->addPath(__DIR__ . '/../Struct');
    }
}
