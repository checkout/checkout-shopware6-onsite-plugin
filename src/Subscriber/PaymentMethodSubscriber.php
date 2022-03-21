<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Subscriber;

use CheckoutCom\Shopware6\CheckoutCom;
use CheckoutCom\Shopware6\Helper\Util;
use CheckoutCom\Shopware6\Struct\Extension\PaymentMethodExtensionStruct;
use ReflectionClass;
use Shopware\Core\Checkout\Payment\PaymentEvents;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentMethodSubscriber implements EventSubscriberInterface
{
    public const PAYMENT_METHOD_EXTENSION = 'checkoutConfig';

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEvents::PAYMENT_METHOD_LOADED_EVENT => 'onPaymentMethodLoaded',
            PaymentEvents::PAYMENT_METHOD_SEARCH_RESULT_LOADED_EVENT => 'onPaymentMethodSearchResultLoaded',
        ];
    }

    public function onPaymentMethodLoaded(EntityLoadedEvent $event): void
    {
        /** @var PaymentMethodEntity $paymentMethod */
        foreach ($event->getEntities() as $paymentMethod) {
            $this->setCustomFieldsPaymentMethod($paymentMethod);
        }
    }

    public function onPaymentMethodSearchResultLoaded(EntitySearchResultLoadedEvent $event): void
    {
        /** @var PaymentMethodEntity $paymentMethod */
        foreach ($event->getResult()->getEntities() as $paymentMethod) {
            $this->setCustomFieldsPaymentMethod($paymentMethod);
        }
    }

    private function setCustomFieldsPaymentMethod(PaymentMethodEntity $paymentMethod): void
    {
        $checkoutComNamespace = (new ReflectionClass(CheckoutCom::class))->getNamespaceName();

        // We check is the payment method is a Checkout.com payment method
        $isCheckoutComPaymentMethod = strpos($paymentMethod->getHandlerIdentifier(), $checkoutComNamespace) !== false;
        $paymentMethodExtension = new PaymentMethodExtensionStruct($isCheckoutComPaymentMethod);
        if ($isCheckoutComPaymentMethod) {
            $paymentMethodType = Util::handleCallUserFunc($paymentMethod->getHandlerIdentifier() . '::getPaymentMethodType', false);
            $paymentMethodExtension->setMethodType($paymentMethodType);
        }

        $paymentMethod->addExtension(self::PAYMENT_METHOD_EXTENSION, $paymentMethodExtension);
    }
}
