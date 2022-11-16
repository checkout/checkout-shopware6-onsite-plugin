<?php declare(strict_types=1);

namespace Cko\Shopware6\Subscriber;

use Cko\Shopware6\CkoShopware6;
use Cko\Shopware6\Factory\SettingsFactory;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Helper\Util;
use Cko\Shopware6\Service\Cart\AbstractCartService;
use Cko\Shopware6\Service\CustomerService;
use Cko\Shopware6\Service\PaymentMethodService;
use Cko\Shopware6\Struct\Customer\CustomerSourceCollection;
use ReflectionClass;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutPaymentMethodPageSubscriber implements EventSubscriberInterface
{
    public const PAYMENT_METHOD_CUSTOM_FIELDS = 'checkoutSource';

    private SettingsFactory $settingsFactory;

    private PaymentMethodService $paymentMethodService;

    private AbstractCartService $cartService;

    public function __construct(
        SettingsFactory $settingsFactory,
        PaymentMethodService $paymentMethodService,
        AbstractCartService $cartService
    ) {
        $this->settingsFactory = $settingsFactory;
        $this->paymentMethodService = $paymentMethodService;
        $this->cartService = $cartService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
            AccountEditOrderPageLoadedEvent::class => 'onAccountEditOrderPageLoaded',
            AccountPaymentMethodPageLoadedEvent::class => 'onAccountPaymentMethodPageLoaded',
        ];
    }

    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $this->addCheckoutSource(false, $event->getPage()->getPaymentMethods(), $event->getSalesChannelContext());
    }

    public function onAccountEditOrderPageLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        $this->addCheckoutSource(false, $event->getPage()->getPaymentMethods(), $event->getSalesChannelContext());
    }

    public function onAccountPaymentMethodPageLoaded(AccountPaymentMethodPageLoadedEvent $event): void
    {
        $this->addCheckoutSource(true, $event->getPage()->getPaymentMethods(), $event->getSalesChannelContext());
    }

    private function addCheckoutSource(bool $setToAll, PaymentMethodCollection $paymentMethods, SalesChannelContext $context): void
    {
        $checkoutComNamespace = (new ReflectionClass(CkoShopware6::class))->getNamespaceName();
        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            return;
        }

        $settings = $this->settingsFactory->getSettings($context->getSalesChannelId());

        // Create a flag to check the current payment method is the same as the hide payment methods
        $isSelectedPaymentMethodRemoved = false;
        foreach ($paymentMethods as $key => $paymentMethod) {
            // We check if the payment method is from Checkout.com.
            $isCheckoutComPaymentMethod = strpos($paymentMethod->getHandlerIdentifier(), $checkoutComNamespace) !== false;
            if (!$isCheckoutComPaymentMethod) {
                continue;
            }

            $paymentHandler = $this->paymentMethodService->getPaymentHandlersByHandlerIdentifier(
                $paymentMethod->getHandlerIdentifier()
            );

            if (!$paymentHandler instanceof PaymentHandler) {
                continue;
            }

            // Remove payment method if payment method should hide by account type settings
            if ($paymentHandler->shouldHideByAccountType($settings->getAccountType())) {
                if ($paymentMethod->getId() === $context->getPaymentMethod()->getId()) {
                    $isSelectedPaymentMethodRemoved = true;
                }

                $paymentMethods->remove($key);

                continue;
            }

            // If the flag is not set to all payment methods, skip if the current payment method is selected
            if (!$setToAll && $paymentMethod->getId() !== $context->getPaymentMethod()->getId()) {
                continue;
            }

            $this->setPaymentMethodSource($paymentMethod, $customer);
        }

        if (!$isSelectedPaymentMethodRemoved) {
            return;
        }

        // Set the first payment method as the selected payment method because the old selected removed
        $firstPaymentMethod = $paymentMethods->first();
        if (!$firstPaymentMethod instanceof PaymentMethodEntity) {
            return;
        }

        $this->cartService->updateContextPaymentMethod($context, $firstPaymentMethod->getId());
        $context->assign([
            'paymentMethod' => $firstPaymentMethod,
        ]);
    }

    private function setPaymentMethodSource(PaymentMethodEntity $paymentMethod, CustomerEntity $customer): void
    {
        $customerCustomFields = CustomerService::getCheckoutSourceCustomFields($customer);
        $paymentMethodType = Util::handleCallUserFunc(
            $paymentMethod->getHandlerIdentifier() . '::getPaymentMethodType',
            false
        );

        $source = $customerCustomFields->getSourceByType($paymentMethodType);
        if (!$source instanceof CustomerSourceCollection) {
            return;
        }

        $customFields = $paymentMethod->getCustomFields() ?? [];
        $customFields[self::PAYMENT_METHOD_CUSTOM_FIELDS] = Util::serializeStruct($source);
        $paymentMethod->setCustomFields($customFields);
    }
}
