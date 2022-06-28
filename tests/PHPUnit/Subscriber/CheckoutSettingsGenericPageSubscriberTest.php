<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Subscriber;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Subscriber\CheckoutSettingsGenericPageSubscriber;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class CheckoutSettingsGenericPageSubscriberTest extends TestCase
{
    use ContextTrait;

    private CheckoutSettingsGenericPageSubscriber $subscriber;

    /**
     * @var SettingsFactory|MockObject
     */
    private $settingFactory;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->settingFactory = $this->createMock(SettingsFactory::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->subscriber = new CheckoutSettingsGenericPageSubscriber($this->settingFactory);
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey(GenericPageLoadedEvent::class, CheckoutSettingsGenericPageSubscriber::getSubscribedEvents());
    }

    public function testOnGenericPageLoaded(): void
    {
        $event = new GenericPageLoadedEvent(
            new CheckoutConfirmPage(),
            $this->salesChannelContext,
            $this->createMock(Request::class)
        );

        $this->settingFactory->expects(static::once())
            ->method('getPublicConfig');

        $this->subscriber->onGenericPageLoaded($event);
    }
}
