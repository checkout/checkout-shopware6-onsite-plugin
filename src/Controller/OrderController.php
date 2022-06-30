<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Controller;

use CheckoutCom\Shopware6\Service\Order\AbstractOrderCheckoutService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
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

    public function __construct(
        DataValidator $dataValidator,
        AbstractOrderCheckoutService $orderCheckoutService
    ) {
        $this->dataValidator = $dataValidator;
        $this->orderCheckoutService = $orderCheckoutService;
    }

    /**
     * Get checkout.com payment by the order id
     *
     * @Route("/api/_action/checkout-com/order/payment", name="api.action.checkout-com.order.payment", methods={"POST"})
     */
    public function getCheckoutComPayment(RequestDataBag $data, Context $context): JsonResponse
    {
        $dataValidation = $this->getCheckoutComPaymentValidation();
        $this->dataValidator->validate($data->all(), $dataValidation);

        $payment = $this->orderCheckoutService->getCheckoutPayment(
            $data->get('orderId'),
            $context
        );

        return new JsonResponse($payment->jsonSerialize());
    }

    /**
     * Capture checkout.com payment by the order id
     *
     * @Route("/api/_action/checkout-com/order/capture", name="api.action.checkout-com.order.capture", methods={"POST"})
     */
    public function capturePayment(RequestDataBag $data, Context $context): JsonResponse
    {
        $dataValidation = $this->getCapturePaymentValidation();
        $this->dataValidator->validate($data->all(), $dataValidation);

        $this->orderCheckoutService->capturePayment(
            $data->get('orderId'),
            $context
        );

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Void checkout.com payment by the order id
     *
     * @Route("/api/_action/checkout-com/order/void", name="api.action.checkout-com.order.void", methods={"POST"})
     */
    public function voidPayment(RequestDataBag $data, Context $context): JsonResponse
    {
        $dataValidation = $this->getVoidPaymentValidation();
        $this->dataValidator->validate($data->all(), $dataValidation);

        $this->orderCheckoutService->voidPayment(
            $data->get('orderId'),
            $context
        );

        return new JsonResponse([
            'success' => true,
        ]);
    }

    public function getCheckoutComPaymentValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.order.payment');

        $definition->add('orderId', new Type('string'), new NotBlank());

        return $definition;
    }

    public function getCapturePaymentValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.order.capture');

        $definition->add('orderId', new Type('string'), new NotBlank());

        return $definition;
    }

    public function getVoidPaymentValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.order.void');

        $definition->add('orderId', new Type('string'), new NotBlank());

        return $definition;
    }
}
