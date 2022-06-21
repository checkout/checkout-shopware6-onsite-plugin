<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Controller;

use CheckoutCom\Shopware6\Service\Order\AbstractOrderService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Controller\PaymentController;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This controller provides an API to replace the route name "payment.finalize.transaction"to finalize the transaction.
 * The reason is that the URL returned from AsyncPaymentTransactionStruct::returnUrl contains the JWT token in its query
 * parameters, but the JWT token contains the encrypted data of Shopware\Core\Checkout\Payment\Cart\Token\TokenStruct,
 * making the URL too long and possibly exceeding the character limit of the browser.
 *
 * @RouteScope(scopes={"api"})
 */
class ReturnUrlController extends AbstractController
{
    private const FINALIZE_JWT_TOKEN = '_sw_payment_token';

    private PaymentController $paymentController;

    private AbstractOrderService $orderService;

    public function __construct(PaymentController $paymentController, AbstractOrderService $orderService)
    {
        $this->paymentController = $paymentController;
        $this->orderService = $orderService;
    }

    /**
     * This API is a replacement for payment.finalize.transaction to finalize the transaction
     *
     * @Route("/api/_action/checkout-com/payment/redirect/finalize-transaction", defaults={"auth_required"=false}, name="api.action.checkout-com.payment.redirect.finalize.url", methods={"GET"}))
     */
    public function returnUrl(Request $request): Response
    {
        $orderId = $request->get('orderId');

        if (!Uuid::isValid($orderId)) {
            throw new OrderNotFoundException($orderId);
        }

        /** @var Context $context */
        $context = $request->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);
        $order = $this->orderService->getOrder($context, $orderId);

        $finalizeRequest = new Request();
        $finalizeRequest->query->set(self::FINALIZE_JWT_TOKEN, $this->getJwtToken($order));

        return $this->paymentController->finalizeTransaction($finalizeRequest);
    }

    /**
     * Get JWT token from order customField
     */
    private function getJwtToken(OrderEntity $order): ?string
    {
        $customField = OrderService::getCheckoutOrderCustomFields($order);
        $transactionReturnUrl = $customField->getTransactionReturnUrl();

        if (!\is_string($transactionReturnUrl)) {
            return null;
        }

        $parts = parse_url($transactionReturnUrl);
        if (\is_array($parts)) {
            parse_str($parts['query'] ?? '', $query);
        }

        return $query['_sw_payment_token'] ?? null;
    }
}
