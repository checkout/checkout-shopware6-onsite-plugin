<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Subscriber;

use Cko\Shopware6\Helper\RequestUtil;
use Cko\Shopware6\Subscriber\HandlePaymentRequestSubscriber;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

class HandlePaymentRequestSubscriberTest extends TestCase
{
    use ContextTrait;

    private HandlePaymentRequestSubscriber $subscriber;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->subscriber = new HandlePaymentRequestSubscriber();
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey(HandlePaymentMethodRouteRequestEvent::class, HandlePaymentRequestSubscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider onHandlePaymentMethodRouteRequestProvider
     */
    public function testOnHandlePaymentMethodRouteRequest(bool $hasKey, string $keyValue, ?string $expected): void
    {
        $dataBagKey = RequestUtil::DATA_BAG_KEY;
        $storeFrontInputBag = new InputBag();
        if ($hasKey) {
            $storeFrontInputBag->set($dataBagKey, $keyValue);
        }

        $storefrontRequest = $this->createMock(Request::class);
        $storefrontRequest->request = $storeFrontInputBag;

        $storeApiInputBag = new InputBag();
        $storeApiRequest = $this->createMock(Request::class);
        $storeApiRequest->request = $storeApiInputBag;

        $event = new HandlePaymentMethodRouteRequestEvent(
            $storefrontRequest,
            $storeApiRequest,
            $this->salesChannelContext
        );

        $this->subscriber->onHandlePaymentMethodRouteRequest($event);

        static::assertSame($event->getStoreApiRequest()->request->get($dataBagKey), $expected);
    }

    public function onHandlePaymentMethodRouteRequestProvider(): array
    {
        return [
            'Test does not has data bag key' => [
                false,
                'Foo-Bar',
                null,
            ],
            'Test has data bag key' => [
                true,
                'Foo-Bar',
                'Foo-Bar',
            ],
        ];
    }
}
