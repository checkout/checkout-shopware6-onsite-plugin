<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Facade\DirectPayFacade;
use CheckoutCom\Shopware6\Struct\Response\AddProductToDirectCartResponse;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SuccessResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
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

    private function getAddProductToDirectCartValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.direct_pay.add_product_to_cart');

        $definition->add('productId', new Type('string'), new NotBlank());
        $definition->add('productQuantity', new Type('integer'), new GreaterThan(0));

        return $definition;
    }
}
