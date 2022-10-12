<?php declare(strict_types=1);

namespace Cko\Shopware6\Subscriber;

use Cko\Shopware6\Helper\RequestUtil;
use Cko\Shopware6\Service\Order\AbstractOrderService;
use Cko\Shopware6\Service\Order\OrderService;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\BeforeSendResponseEvent;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

class PaymentBeforeSendResponseEventSubscriber implements EventSubscriberInterface
{
    private RouterInterface $router;

    private LoggerInterface $logger;

    private AbstractOrderService $orderService;

    public function __construct(RouterInterface $router, LoggerInterface $logger, AbstractOrderService $orderService)
    {
        $this->router = $router;
        $this->logger = $logger;
        $this->orderService = $orderService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeSendResponseEvent::class => 'onBeforeSendResponse',
        ];
    }

    /**
     * Because there are some payment methods required to be sent as an AJAX request,
     * and expect to receive a JsonResponse, we need to convert all responses to JsonResponse,
     * so these payment method handlers will get the payment status (Success/Failure)
     * and handle it on Storefront.
     * Payment Methods:
     *  - Apple Pay
     *  - Google Pay
     *  - ...
     *  All these payment methods need to send a `json` key value to the backend,
     *  so it can be later converted to a JsonResponse
     */
    public function onBeforeSendResponse(BeforeSendResponseEvent $event): void
    {
        $request = $event->getRequest();

        $paymentData = $this->getPaymentData($request);
        if (!$paymentData instanceof RequestDataBag) {
            return;
        }

        try {
            $orderId = $this->getOrderIdFromRequest($request);

            $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);
            if (!$context instanceof Context) {
                throw new Exception('Missing context when process convert response');
            }

            $order = $this->orderService->getOrder($context, $orderId);
            $checkoutOrderCustomFields = OrderService::getCheckoutOrderCustomFields($order);

            if (!empty($checkoutOrderCustomFields->getCheckoutPaymentId())) {
                $response = $event->getResponse();

                // If the checkout.com payment ID is set and the response is a redirect
                // it means that the payment was successful,
                // so we can get the redirect URL from the response
                if ($response instanceof RedirectResponse) {
                    $event->setResponse(new JsonResponse([
                        'success' => true,
                        'redirectUrl' => $response->getTargetUrl(),
                    ]));

                    return;
                }
            }

            $event->setResponse(new JsonResponse([
                'success' => false,
                'redirectUrl' => $this->generateUrl('frontend.checkout.finish.page', [
                    'orderId' => $orderId,
                    'changedPayment' => false,
                    'paymentFailed' => true,
                ]),
            ]));
        } catch (Throwable $e) {
            $this->logger->critical('Unknown error when trying to process convert response', [
                'paymentData' => $paymentData,
                'error' => $e->getMessage(),
            ]);

            $event->setResponse(new JsonResponse([
                'success' => false,
                'redirectUrl' => $this->generateUrl('frontend.checkout.confirm.page'),
            ]));
        }
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @see UrlGeneratorInterface
     */
    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): string {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    /**
     * Get the checkout payment data from the request.
     * This data is our plugin's payment request data
     */
    private function getPaymentData(Request $request): ?RequestDataBag
    {
        $requestBag = new RequestDataBag($request->request->all());
        $paymentData = RequestUtil::getPaymentData($requestBag);

        if (!$paymentData instanceof RequestDataBag) {
            // It means that the requested data is not our plugin's request payment data
            return null;
        }

        // Skip if the request does not need JSON response
        if (!$paymentData->get(RequestUtil::DATA_JSON)) {
            return null;
        }

        return $paymentData;
    }

    /**
     * The order ID is coming from the request of edit-order page
     * If the order ID is not found from edit-order page,
     * it means the order ID is created by Shopware Core
     *
     * @throws Exception
     */
    private function getOrderIdFromRequest(Request $request): string
    {
        // Get order ID from the request
        // This case happens for the edit order page
        $orderId = $request->get('orderId');

        // In case of create order (empty edit order ID), It needs to get the last order ID
        // This order ID is created by the Shopware Core
        $orderId = $orderId ?? $this->orderService->getRequestLastOrderId();

        // The order was not created, return the checkout confirmation page
        if (empty($orderId)) {
            throw new Exception('The order was not created');
        }

        return $orderId;
    }
}
