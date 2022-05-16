<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Facade\DirectPayFacade;
use CheckoutCom\Shopware6\Handler\Method\ApplePayHandler;
use CheckoutCom\Shopware6\Struct\Response\AddProductToDirectCartResponse;
use CheckoutCom\Shopware6\Struct\Response\DirectProcessResponse;
use CheckoutCom\Shopware6\Struct\Response\DirectShippingResponse;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SuccessResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

/**
 * This controller will handle direct payment requests
 *
 * @RouteScope(scopes={"store-api"})
 */
class DirectPayController extends AbstractDirectPayController
{
    private DataValidator $dataValidator;

    private DirectPayFacade $directPayFacade;

    public function __construct(
        DataValidator $dataValidator,
        DirectPayFacade $directPayFacade
    ) {
        $this->dataValidator = $dataValidator;
        $this->directPayFacade = $directPayFacade;
    }

    public function getDecorated(): AbstractDirectPayController
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Add product to our plugin checkout cart
     *
     * @OA\Post(
     *      path="/checkout-com/direct/add-product-to-cart",
     *      summary="Add product to cart",
     *      description="Add product to cart",
     *      operationId="checkoutComDirectAddProductToCart",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              required={"productId", "productQuantity"},
     *              @OA\Property(
     *                  property="productId",
     *                  description="Product ID",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="productQuantity",
     *                  description="Product quantity",
     *                  type="integer"
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Returns a success response.",
     *          @OA\JsonContent(ref="#/components/schemas/checkout_com_add_product_to_direct_cart_response")
     *     )
     * )
     * @Route("/store-api/checkout-com/direct/add-product-to-cart", name="store-api.checkout-com.direct.add-product-to-cart", methods={"POST"}, options={"seo"="false"})
     */
    public function addProductToDirectCart(SalesChannelContext $context, RequestDataBag $data): AddProductToDirectCartResponse
    {
        $dataValidation = $this->getAddProductToDirectCartValidation();
        $this->dataValidator->validate($data->all(), $dataValidation);

        $cart = $this->directPayFacade->addProductToCart(
            $data->get('productId'),
            $data->getInt('productQuantity'),
            $context
        );

        return new AddProductToDirectCartResponse(true, $cart->getToken());
    }

    /**
     * Clear all the back-up cart
     *
     * @OA\Post(
     *      path="/checkout-com/direct/remove-backup",
     *      summary="Clear all the back-up cart",
     *      description="Clear all the back-up cart",
     *      operationId="checkoutComDirectRemoveBackup",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="cartToken",
     *                  description="Cart token",
     *                  type="string"
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Returns a success response.",
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     * @Route("/store-api/checkout-com/direct/remove-backup", name="store-api.checkout-com.direct.remove-backup", methods={"POST"}, defaults={"csrf_protected"=false}, options={"seo"="false"})
     */
    public function removeBackUp(SalesChannelContext $context, RequestDataBag $data): SuccessResponse
    {
        $this->directPayFacade->removeBackupCarts($data->get('cartToken'), $context);

        return new SuccessResponse();
    }

    /**
     * Get shipping methods for direct pay and calculate the direct cart
     * using the selected shipping method in the context
     *
     * @OA\Post(
     *      path="/checkout-com/direct/get-shipping-methods",
     *      summary="Get shipping methods for direct pay and calculate the direct cart",
     *      description="Get shipping methods for direct pay and calculate the direct cart by using the selected shipping method in the context",
     *      operationId="checkoutComDirectGetShippingOptions",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              required={"paymentMethodType", "countryCode"},
     *              @OA\Property(
     *                  property="paymentMethodType",
     *                  description="Payment method type",
     *                  type="string",
     *                  example="applepay"
     *              ),
     *              @OA\Property(
     *                  property="cartToken",
     *                  description="Cart token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="countryCode",
     *                  description="Country Code",
     *                  type="string"
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Returns the result of direct pay shipping methods.",
     *          @OA\JsonContent(ref="#/components/schemas/checkout_com_direct_shipping_response")
     *     )
     * )
     * @Route("/store-api/checkout-com/direct/get-shipping-methods", name="store-api.checkout-com.direct.get-shipping-methods", methods={"POST"}, defaults={"csrf_protected"=false}, options={"seo"="false"})
     */
    public function getShippingMethods(SalesChannelContext $context, RequestDataBag $data): DirectShippingResponse
    {
        $dataValidation = $this->getShippingMethodsValidation();
        $this->dataValidator->validate($data->all(), $dataValidation);

        return $this->directPayFacade->getShippingMethodsResponse($data, $context);
    }

    /**
     * Calculate the direct cart for the specific shipping method
     *
     * @OA\Post(
     *      path="/checkout-com/direct/update-shipping-payload",
     *      summary="Calculate the direct cart for the specific shipping method",
     *      description="Calculate the direct cart for the specific shipping method",
     *      operationId="checkoutComDirectGetShippingOption",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              required={"paymentMethodType", "shippingMethodId"},
     *              @OA\Property(
     *                  property="paymentMethodType",
     *                  description="Payment method type",
     *                  type="string",
     *                  example="applepay"
     *              ),
     *              @OA\Property(
     *                  property="cartToken",
     *                  description="Cart token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="shippingMethodId",
     *                  description="Shipping method ID",
     *                  type="string"
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Returns the result of select shipping method",
     *          @OA\JsonContent(ref="#/components/schemas/checkout_com_direct_shipping_response")
     *     ),
     * )
     * @Route("/store-api/checkout-com/direct/update-shipping-payload", name="store-api.checkout-com.direct.update-shipping-payload", methods={"POST"}, defaults={"csrf_protected"=false}, options={"seo"="false"})
     */
    public function updateShippingPayload(SalesChannelContext $context, RequestDataBag $data): DirectShippingResponse
    {
        $dataValidation = $this->getSetShippingMethodValidation();
        $this->dataValidator->validate($data->all(), $dataValidation);

        return $this->directPayFacade->updateShippingPayloadResponse($data, $context);
    }

    /**
     * Process payment for direct pay
     *
     * @OA\Post(
     *      path="/checkout-com/direct/process-payment",
     *      summary="Process payment for direct pay",
     *      description="Process payment for direct pay",
     *      operationId="checkoutComDirectProcessPayment",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              required={"paymentMethodType", "cartToken", "shippingContact"},
     *              @OA\Property(
     *                  property="paymentMethodType",
     *                  description="Payment method type",
     *                  type="string",
     *                  example="applepay"
     *              ),
     *              @OA\Property(
     *                  property="cartToken",
     *                  description="Cart token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  required={"countryCode", "firstName", "lastName", "email", "street", "zipCode", "city"},
     *                  property="shippingContact",
     *                  type="object",
     *                  description="Shipping contact object",
     *                  @OA\Property(
     *                      property="countryCode",
     *                      type="string",
     *                      description="Country code"
     *                  ),
     *                  @OA\Property(
     *                      property="countryStateCode",
     *                      type="string",
     *                      description="Country state code"
     *                  ),
     *                  @OA\Property(
     *                      property="firstName",
     *                      type="string",
     *                      description="First name"
     *                  ),
     *                  @OA\Property(
     *                      property="lastName",
     *                      type="string",
     *                      description="Last name"
     *                  ),
     *                  @OA\Property(
     *                      property="email",
     *                      type="string",
     *                      description="Email"
     *                  ),
     *                  @OA\Property(
     *                      property="phoneNumber",
     *                      type="string",
     *                      description="Phone number"
     *                  ),
     *                  @OA\Property(
     *                      property="street",
     *                      type="string",
     *                      description="Street"
     *                  ),
     *                  @OA\Property(
     *                      property="additionalAddressLine1",
     *                      type="string",
     *                      description="Additional address line 1"
     *                  ),
     *                  @OA\Property(
     *                      property="zipCode",
     *                      type="string",
     *                      description="Zip code"
     *                  ),
     *                  @OA\Property(
     *                      property="city",
     *                      type="string",
     *                      description="City"
     *                  ),
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Returns the result of the process payment",
     *         @OA\JsonContent(ref="#/components/schemas/checkout_com_direct_process_response")
     *     )
     * )
     * @Route("/store-api/checkout-com/direct/process-payment", name="store-api.checkout-com.direct.process-payment", methods={"POST"}, defaults={"csrf_protected"=false}, options={"seo"="false"})
     */
    public function processPayment(SalesChannelContext $context, RequestDataBag $data): DirectProcessResponse
    {
        $dataValidation = $this->getProcessPaymentValidation();
        $this->dataValidator->validate($data->all(), $dataValidation);

        return $this->directPayFacade->processPayment($context, $data);
    }

    private function getAddProductToDirectCartValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.direct_pay.add_product_to_cart');

        $definition->add('productId', new Type('string'), new NotBlank());
        $definition->add('productQuantity', new Type('integer'), new GreaterThan(0));

        return $definition;
    }

    private function getShippingMethodsValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.direct_pay.direct.select_shipping_methods');

        $definition->add('paymentMethodType', new Type('string'), new Choice($this->getAvailablePaymentMethods()));
        $definition->add('countryCode', new Type('string'), new NotBlank());
        $definition->add('cartToken', new Type('string'), new NotBlank());

        return $definition;
    }

    private function getSetShippingMethodValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.direct_pay.direct.set_shipping_method');

        $definition->add('paymentMethodType', new Type('string'), new Choice($this->getAvailablePaymentMethods()));
        $definition->add('shippingMethodId', new Type('string'), new NotBlank());
        $definition->add('cartToken', new Type('string'), new NotBlank());

        return $definition;
    }

    private function getProcessPaymentValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.direct_pay.direct.set_shipping_method');

        $definition->add('paymentMethodType', new Type('string'), new Choice($this->getAvailablePaymentMethods()));
        $definition->add('cartToken', new Type('string'), new NotBlank());
        $definition->add('shippingContact', new Collection([
            'fields' => [
                'lastName' => [new Type('string'), new NotBlank()],
                'firstName' => [new Type('string'), new NotBlank()],
                'email' => [new Type('string'), new NotBlank()],
                'phoneNumber' => [new Optional(new Type('string'))],
                'street' => [new Optional(new Type('string'))],
                'additionalAddressLine1' => [new Optional(new Type('string'))],
                'zipCode' => [new Type('string'), new NotBlank()],
                'countryStateCode' => [new Optional(new Type('string'))],
                'city' => [new Type('string'), new NotBlank()],
                'countryCode' => [new Type('string'), new NotBlank()],
            ],
            'allowExtraFields' => true,
            'allowMissingFields' => false,
        ]));

        return $definition;
    }

    /**
     * Get available direct payment methods
     * At the moment we only support Apple Pay direct pay
     */
    private function getAvailablePaymentMethods(): array
    {
        return [
            ApplePayHandler::getPaymentMethodType(),
        ];
    }
}
