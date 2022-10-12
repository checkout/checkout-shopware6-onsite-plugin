<?php declare(strict_types=1);

namespace Cko\Shopware6\Subscriber;

use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Helper\Util;
use Cko\Shopware6\Service\PaymentMethodService;
use Cko\Shopware6\Struct\PaymentMethod\PaymentMethodCustomFieldsStruct;
use Shopware\Core\Checkout\Payment\PaymentEvents;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentMethodSubscriber implements EventSubscriberInterface
{
    public const PAYMENT_METHOD_CUSTOM_FIELDS = 'checkoutConfig';

    private PaymentMethodService $paymentMethodService;

    public function __construct(PaymentMethodService $paymentMethodService)
    {
        $this->paymentMethodService = $paymentMethodService;
    }

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
        $paymentHandler = $this->paymentMethodService->getPaymentHandlersByHandlerIdentifier(
            $paymentMethod->getHandlerIdentifier()
        );

        if (!$paymentHandler instanceof PaymentHandler) {
            return;
        }

        $paymentMethodCustomFields = new PaymentMethodCustomFieldsStruct();
        $paymentMethodCustomFields->setMethodType(
            Util::handleCallUserFunc(
                $paymentMethod->getHandlerIdentifier() . '::getPaymentMethodType',
                false
            )
        );
        $customFields = $paymentMethod->getCustomFields() ?? [];
        $customFields[self::PAYMENT_METHOD_CUSTOM_FIELDS] = $paymentMethodCustomFields->jsonSerialize();
        $paymentMethod->setCustomFields($customFields);
    }
}
