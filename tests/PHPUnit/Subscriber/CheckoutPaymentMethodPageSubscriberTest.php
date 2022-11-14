<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Subscriber;

use Cko\Shopware6\Factory\SettingsFactory;
use Cko\Shopware6\Handler\Method\CardPaymentHandler;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Service\Cart\AbstractCartService;
use Cko\Shopware6\Service\CustomerService;
use Cko\Shopware6\Service\PaymentMethodService;
use Cko\Shopware6\Subscriber\CheckoutPaymentMethodPageSubscriber;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Cko\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPage;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class CheckoutPaymentMethodPageSubscriberTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    /**
     * @var MockObject|SettingsFactory
     */
    private $settingsFactory;

    /**
     * @var MockObject|PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var MockObject|AbstractCartService
     */
    private $cartService;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    private CheckoutPaymentMethodPageSubscriber $subscriber;

    public function setUp(): void
    {
        $this->settingsFactory = $this->createMock(SettingsFactory::class);
        $this->paymentMethodService = $this->createMock(PaymentMethodService::class);
        $this->cartService = $this->createMock(AbstractCartService::class);
        $this->subscriber = new CheckoutPaymentMethodPageSubscriber(
            $this->settingsFactory,
            $this->paymentMethodService,
            $this->cartService,
        );
        $this->salesChannelContext = $this->getSaleChannelContext($this);
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey(CheckoutConfirmPageLoadedEvent::class, CheckoutPaymentMethodPageSubscriber::getSubscribedEvents());
        static::assertArrayHasKey(AccountEditOrderPageLoadedEvent::class, CheckoutPaymentMethodPageSubscriber::getSubscribedEvents());
        static::assertArrayHasKey(AccountPaymentMethodPageLoadedEvent::class, CheckoutPaymentMethodPageSubscriber::getSubscribedEvents());
    }

    public function testOnCheckoutConfirmPageLoaded(): void
    {
        $paymentMethod = $this->getCheckoutPaymentMethod();
        $page = new CheckoutConfirmPage();
        $page->setPaymentMethods(new PaymentMethodCollection([$paymentMethod]));
        $customer = $this->getCustomer();
        $this->salesChannelContext->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $this->salesChannelContext->method('getCustomer')
            ->willReturn($customer);

        $this->paymentMethodService->method('getPaymentHandlersByHandlerIdentifier')
            ->willReturn(
                $this->createConfiguredMock(PaymentHandler::class, [
                    'shouldHideByAccountType' => false,
                ])
            );

        $event = new CheckoutConfirmPageLoadedEvent(
            $page,
            $this->salesChannelContext,
            $this->createMock(Request::class)
        );

        $this->subscriber->onCheckoutConfirmPageLoaded($event);
        $customFields = $paymentMethod->getCustomFields();

        static::assertArrayHasKey(CheckoutPaymentMethodPageSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS, $customFields);
        static::assertIsArray($customFields[CheckoutPaymentMethodPageSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS]);
    }

    public function testOnAccountEditOrderPageLoaded(): void
    {
        $paymentMethod = $this->getCheckoutPaymentMethod();
        $page = new AccountEditOrderPage();
        $page->setPaymentMethods(new PaymentMethodCollection([$paymentMethod]));
        $customer = $this->getCustomer();
        $this->salesChannelContext->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $this->salesChannelContext->method('getCustomer')
            ->willReturn($customer);

        $this->paymentMethodService->method('getPaymentHandlersByHandlerIdentifier')
            ->willReturn(
                $this->createConfiguredMock(PaymentHandler::class, [
                    'shouldHideByAccountType' => false,
                ])
            );

        $event = new AccountEditOrderPageLoadedEvent(
            $page,
            $this->salesChannelContext,
            $this->createMock(Request::class)
        );

        $this->subscriber->onAccountEditOrderPageLoaded($event);
        $customFields = $paymentMethod->getCustomFields();

        static::assertArrayHasKey(CheckoutPaymentMethodPageSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS, $customFields);
        static::assertIsArray($customFields[CheckoutPaymentMethodPageSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS]);
    }

    public function testOnAccountPaymentMethodPageLoaded(): void
    {
        $paymentMethod = $this->getCheckoutPaymentMethod();
        $page = new AccountPaymentMethodPage();
        $page->setPaymentMethods(new PaymentMethodCollection([$paymentMethod]));
        $customer = $this->getCustomer();
        $this->salesChannelContext->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $this->salesChannelContext->method('getCustomer')
            ->willReturn($customer);

        $this->paymentMethodService->method('getPaymentHandlersByHandlerIdentifier')
            ->willReturn(
                $this->createConfiguredMock(PaymentHandler::class, [
                    'shouldHideByAccountType' => false,
                ])
            );

        $event = new AccountPaymentMethodPageLoadedEvent(
            $page,
            $this->salesChannelContext,
            $this->createMock(Request::class)
        );

        $this->subscriber->onAccountPaymentMethodPageLoaded($event);
        $customFields = $paymentMethod->getCustomFields();

        static::assertArrayHasKey(CheckoutPaymentMethodPageSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS, $customFields);
        static::assertIsArray($customFields[CheckoutPaymentMethodPageSubscriber::PAYMENT_METHOD_CUSTOM_FIELDS]);
    }

    public function getCheckoutPaymentMethod(): PaymentMethodEntity
    {
        $entity = new PaymentMethodEntity();
        $entity->setId(Uuid::randomHex());
        $entity->setHandlerIdentifier(CardPaymentHandler::class);

        return $entity;
    }

    public function getCustomer(): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setId('foo');
        $customer->setCustomFields([
            CustomerService::CHECKOUT_SOURCE_CUSTOM_FIELDS => [
                CardPaymentHandler::getPaymentMethodType() => [
                    ['id' => 'foo'],
                    ['id' => 'bar'],
                ],
            ],
        ]);

        return $customer;
    }
}
