<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Controller;

use CheckoutCom\Shopware6\Facade\PaymentRefundFacade;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderCheckoutService;
use CheckoutCom\Shopware6\Struct\Request\Refund\OrderRefundRequest;
use CheckoutCom\Shopware6\Struct\Request\Refund\RefundItemRequest;
use CheckoutCom\Shopware6\Struct\Request\Refund\RefundItemRequestCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * This controller handles all tasks related to order.
 *
 * @RouteScope(scopes={"api"})
 */
class OrderController extends AbstractController
{
    private DataValidator $dataValidator;

    private AbstractOrderCheckoutService $orderCheckoutService;

    private PaymentRefundFacade $paymentRefundFacade;

    public function __construct(
        DataValidator $dataValidator,
        AbstractOrderCheckoutService $orderCheckoutService,
        PaymentRefundFacade $paymentRefundFacade
    ) {
        $this->dataValidator = $dataValidator;
        $this->orderCheckoutService = $orderCheckoutService;
        $this->paymentRefundFacade = $paymentRefundFacade;
    }

    /**
     * Get checkout.com payment by the order id
     *
     * @Route("/api/_action/checkout-com/order/payment/{orderId}", name="api.action.checkout-com.order.payment", methods={"POST"})
     */
    public function getCheckoutComPayment(string $orderId, Context $context): JsonResponse
    {
        $payment = $this->orderCheckoutService->getCheckoutPayment(
            $orderId,
            $context
        );

        return new JsonResponse($payment->jsonSerialize());
    }

    /**
     * Capture checkout.com payment by the order id
     *
     * @Route("/api/_action/checkout-com/order/capture/{orderId}", name="api.action.checkout-com.order.capture", methods={"POST"})
     */
    public function capturePayment(string $orderId, Context $context): JsonResponse
    {
        $this->orderCheckoutService->capturePayment(
            $orderId,
            $context
        );

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Void checkout.com payment by the order id
     *
     * @Route("/api/_action/checkout-com/order/void/{orderId}", name="api.action.checkout-com.order.void", methods={"POST"})
     */
    public function voidPayment(string $orderId, Context $context): JsonResponse
    {
        $this->orderCheckoutService->voidPayment(
            $orderId,
            $context
        );

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Refund checkout.com payment by the order id
     *
     * @Route("/api/_action/checkout-com/order/refund", name="api.action.checkout-com.order.refund", methods={"POST"})
     */
    public function refundPayment(RequestDataBag $request, Context $context): JsonResponse
    {
        $dataValidation = $this->getRefundPaymentValidation();
        $data = $request->all();
        $this->dataValidator->validate($data, $dataValidation);

        $orderRefundRequest = $this->buildOrderRefundRequest($data);

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $context
        );

        return new JsonResponse([
            'success' => true,
        ]);
    }

    private function getRefundPaymentValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.order.refund');

        $definition->add('orderId', new Type('string'), new NotBlank());
        $definition->add(
            'items',
            new Count([
                'min' => 1,
            ]),
            new All([
                'constraints' => [
                    new Collection([
                        'fields' => [
                            'id' => [new Type('string'), new NotBlank()],
                            'returnQuantity' => [new NotBlank(), new Type('numeric'), new GreaterThan(0)],
                        ],
                    ]),
                ],
            ])
        );

        return $definition;
    }

    private function buildOrderRefundRequest(array $data): OrderRefundRequest
    {
        $refundItems = new RefundItemRequestCollection();

        foreach ($data['items'] as $item) {
            $refundItem = new RefundItemRequest();
            $refundItem->setId($item['id']);
            $refundItem->setReturnQuantity($item['returnQuantity']);
            $refundItems->set($refundItem->getId(), $refundItem);
        }

        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId($data['orderId']);
        $orderRefundRequest->setItems($refundItems);

        return $orderRefundRequest;
    }
}
