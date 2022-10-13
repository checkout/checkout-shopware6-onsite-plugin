<?php declare(strict_types=1);

namespace Cko\Shopware6\Storefront\Api;

use Cko\Shopware6\Service\CustomerService;
use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SuccessResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * This controller will handle source of the customer
 *
 * @RouteScope(scopes={"store-api"})
 */
class SourceController
{
    private DataValidator $dataValidator;

    private CustomerService $customerService;

    public function __construct(DataValidator $dataValidator, CustomerService $customerService)
    {
        $this->dataValidator = $dataValidator;
        $this->customerService = $customerService;
    }

    /**
     * The source ID of the payment method needs to be deleted from the customer
     *
     * @OA\Delete(
     *      path="/checkout-com/source",
     *      summary="The source ID needs to be deleted",
     *      description="The source ID of the payment method needs to be deleted from the customer",
     *      operationId="checkoutComSourceDelete",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              required={"sourceId"},
     *              @OA\Property(
     *                  property="sourceId",
     *                  description="The source ID of the payment method needs to delete from the customer",
     *                  type="string"
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response="204",
     *          description="Successfully deleted the source",
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     * @Route("/store-api/checkout-com/source", name="store-api.checkout-com.source.remove", methods={"DELETE"})
     */
    public function deleteSource(RequestDataBag $data, SalesChannelContext $context): SuccessResponse
    {
        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw new CustomerNotLoggedInException();
        }

        $dataValidation = $this->getRemoveSourceValidation();
        $this->dataValidator->validate($data->all(), $dataValidation);

        $this->customerService->removeCustomerSource(
            $data->get('sourceId'),
            $customer,
            $context
        );

        return new SuccessResponse();
    }

    private function getRemoveSourceValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.source.remove_source');

        $definition->add('sourceId', new Type('string'), new NotBlank());

        return $definition;
    }
}
