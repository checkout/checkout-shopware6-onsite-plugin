<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Subscriber;

use CheckoutCom\Shopware6\Helper\RequestUtil;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HandlePaymentRequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            HandlePaymentMethodRouteRequestEvent::class => 'onHandlePaymentMethodRouteRequest',
        ];
    }

    /**
     * Modify the payment method route request to add payment details from the StoreFront request to the API request
     */
    public function onHandlePaymentMethodRouteRequest(HandlePaymentMethodRouteRequestEvent $event): void
    {
        $storeFrontRequest = $event->getStorefrontRequest();
        $dataBagKey = RequestUtil::DATA_BAG_KEY;

        // We skip if the storefront request does not have the checkout request key
        if (!$storeFrontRequest->request->has($dataBagKey)) {
            return;
        }

        $paymentDetails = $storeFrontRequest->request->get($dataBagKey);

        /*
         * Have to add to store api request from storefront request
         * Because when the storefront request is sent to the API on order edit screen, the payment details are not available
         *
         * @see \Shopware\Storefront\Controller\AccountOrderController::updateOrder
         */
        $event->getStoreApiRequest()->request->set($dataBagKey, $paymentDetails);
    }
}
