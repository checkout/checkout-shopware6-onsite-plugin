<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Controller;

use CheckoutCom\Shopware6\Handler\Method\ApplePayHandler;
use CheckoutCom\Shopware6\Service\CustomerService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class ComponentsController extends StorefrontController
{
    private CustomerService $customerService;

    private PaymentMethodService $paymentMethodService;

    public function __construct(CustomerService $customerService, PaymentMethodService $paymentMethodService)
    {
        $this->customerService = $customerService;
        $this->paymentMethodService = $paymentMethodService;
    }

    /**
     * Store card Token to customer
     *
     * @Route("/widgets/checkout-com/store-card-token/{customerId}", name="frontend.checkout-com.store-card-token", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function storeCardToken(SalesChannelContext $context, RequestDataBag $data, string $customerId): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomer($customerId, $context);
        } catch (CustomerNotFoundByIdException $e) {
            return new JsonResponse([
                'success' => false,
                'result' => $e->getMessage(),
            ]);
        }

        $result = $this->customerService->setCardToken(
            $customer,
            $data->get('cardToken'),
            $context
        );

        return new JsonResponse([
            'success' => (bool) $result,
            'result' => $result->getErrors(),
        ]);
    }

    /**
     * Get apple pay payment method id
     *
     * @Route("/widgets/checkout-com/apple-pay-id", name="frontend.checkout-com.apple-pay-id", options={"seo"="false"}, methods={"GET"}, defaults={"XmlHttpRequest"=true})
     */
    public function getApplePayId(SalesChannelContext $context): JsonResponse
    {
        $applePay = $this->paymentMethodService->getPaymentMethodByHandlerIdentifier(
            $context->getContext(),
            ApplePayHandler::class,
            true
        );

        return new JsonResponse([
            'id' => $applePay instanceof PaymentMethodEntity ? $applePay->getId() : null,
        ]);
    }
}
