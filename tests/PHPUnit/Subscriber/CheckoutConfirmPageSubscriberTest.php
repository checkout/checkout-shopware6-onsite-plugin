<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Subscriber;

use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Helper\Url;
use CheckoutCom\Shopware6\Struct\Extension\ConfirmPageExtensionStruct;
use CheckoutCom\Shopware6\Struct\SettingStruct;
use CheckoutCom\Shopware6\Subscriber\CheckoutConfirmPageSubscriber;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class CheckoutConfirmPageSubscriberTest extends TestCase
{
    use ContextTrait;

    private CheckoutConfirmPageSubscriber $subscriber;

    /**
     * @var SettingsFactory|MockObject
     */
    private $settingFactory;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->settingFactory = $this->createMock(SettingsFactory::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->subscriber = new CheckoutConfirmPageSubscriber($this->settingFactory);
    }

    public function setUpPageLoaderEvent(bool $sandbox, string $publicKey): void
    {
        $settings = new SettingStruct();
        $settings->setSandboxMode($sandbox);
        $settings->setPublicKey($publicKey);

        $this->settingFactory->method('getSettings')->willReturn($settings);
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey(CheckoutConfirmPageLoadedEvent::class, CheckoutConfirmPageSubscriber::getSubscribedEvents());
        static::assertArrayHasKey(AccountEditOrderPageLoadedEvent::class, CheckoutConfirmPageSubscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider pageLoadedEventProvider
     */
    public function testAddCheckoutSettingsToCheckoutConfirmPageCorrect(bool $sandbox, string $publicKey): void
    {
        $event = new CheckoutConfirmPageLoadedEvent(
            new CheckoutConfirmPage(),
            $this->salesChannelContext,
            $this->createMock(Request::class)
        );

        $this->setUpPageLoaderEvent($sandbox, $publicKey);
        $this->subscriber->onCheckoutConfirmPageLoaded($event);

        static::assertTrue($event->getPage()->hasExtension(CheckoutConfirmPageSubscriber::CONFIRM_PAGE_EXTENSION));

        $confirmPageExtension = $event->getPage()->getExtension(CheckoutConfirmPageSubscriber::CONFIRM_PAGE_EXTENSION);

        static::assertInstanceOf(ConfirmPageExtensionStruct::class, $confirmPageExtension);

        /** @var ConfirmPageExtensionStruct $confirmPageExtension */
        static::assertSame($sandbox, $confirmPageExtension->isSandboxMode());
        static::assertSame($publicKey, $confirmPageExtension->getPublicKey());
        static::assertSame(Url::IFRAME_URL, $confirmPageExtension->getFrameUrl());
    }

    /**
     * @dataProvider pageLoadedEventProvider
     */
    public function testAddCheckoutSettingsToAccountEditOrderPageCorrect(bool $sandbox, string $publicKey): void
    {
        $event = new AccountEditOrderPageLoadedEvent(
            new AccountEditOrderPage(),
            $this->salesChannelContext,
            $this->createMock(Request::class)
        );

        $this->setUpPageLoaderEvent($sandbox, $publicKey);
        $this->subscriber->onAccountEditOrderPageLoaded($event);

        static::assertTrue($event->getPage()->hasExtension(CheckoutConfirmPageSubscriber::CONFIRM_PAGE_EXTENSION));

        $confirmPageExtension = $event->getPage()->getExtension(CheckoutConfirmPageSubscriber::CONFIRM_PAGE_EXTENSION);

        static::assertInstanceOf(ConfirmPageExtensionStruct::class, $confirmPageExtension);

        /** @var ConfirmPageExtensionStruct $confirmPageExtension */
        static::assertSame($sandbox, $confirmPageExtension->isSandboxMode());
        static::assertSame($publicKey, $confirmPageExtension->getPublicKey());
        static::assertSame(Url::IFRAME_URL, $confirmPageExtension->getFrameUrl());
    }

    public function pageLoadedEventProvider(): array
    {
        return [
            'Test is sandbox mode' => [true, 'TestPublicKey'],
            'Test is production mode' => [false, 'Test other public keykey'],
        ];
    }
}
