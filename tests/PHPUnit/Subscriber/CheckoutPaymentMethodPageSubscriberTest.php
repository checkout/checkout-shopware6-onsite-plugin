<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Subscriber;

use CheckoutCom\Shopware6\Handler\Method\CardPaymentHandler;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\Cart\AbstractCartService;
use CheckoutCom\Shopware6\Service\CustomerService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Subscriber\CheckoutPaymentMethodPageSubscriber;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
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
     * @var PaymentMethodService|MockObject
     */
    private $paymentMethodService;

    /**
     * @var AbstractCartService|MockObject
     */
    private $cartService;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    private CheckoutPaymentMethodPageSubscriber $subscriber;

    public function setUp(): void
    {
        $this->paymentMethodService = $this->createMock(PaymentMethodService::class);
        $this->cartService = $this->createMock(AbstractCartService::class);
        $this->subscriber = new CheckoutPaymentMethodPageSubscriber(
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
                    'getAvailableCountries' => [],
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
                    'getAvailableCountries' => [],
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
                    'getAvailableCountries' => [],
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

        $customer->setActiveBillingAddress(
            $this->createConfiguredMock(CustomerAddressEntity::class, [
                'getId' => 'foo',
                'getCountry' => $this->createConfiguredMock(CountryEntity::class, [
                    'getId' => 'foo',
                    'getIso' => 'foo',
                ]),
            ])
        );

        return $customer;
    }
}
