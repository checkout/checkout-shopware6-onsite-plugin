<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Service\ApplePay\AbstractApplePayService;
use CheckoutCom\Shopware6\Struct\Response\MerchantSessionResponse;
use GuzzleHttp\Exception\GuzzleException;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @RouteScope(scopes={"store-api"})
 */
class ApplePayController extends AbstractApplePayController
{
    private DataValidator $dataValidator;

    private AbstractApplePayService $applePayService;

    public function __construct(DataValidator $dataValidator, AbstractApplePayService $applePayService)
    {
        $this->dataValidator = $dataValidator;
        $this->applePayService = $applePayService;
    }

    public function getDecorated(): AbstractApplePayController
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Validate merchant for Apple Pay
     *
     * @OA\Post(
     *      path="/checkout-com/validate-merchant",
     *      summary="Validate merchant for Apple Pay",
     *      description="Validate merchant for Apple Pay",
     *      operationId="checkoutComValidateMerchant",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              required={"validationURL"},
     *              @OA\Property(
     *                  property="validationURL",
     *                  description="Apple validation URL return from Apple Pay on merchant validation",
     *                  type="string"
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Returns the object/null of the apple pay merchant",
     *         @OA\JsonContent(ref="#/components/schemas/checkout_com_merchant_session_response")
     *     )
     * )
     * @Route("/store-api/checkout-com/validate-merchant", name="store-api.checkout-com.validate-merchant", methods={"POST"})
     *
     * @throws GuzzleException
     */
    public function validateMerchant(SalesChannelContext $context, RequestDataBag $data, Request $request): MerchantSessionResponse
    {
        $definition = new DataValidationDefinition('apple_pay_controller.validate_merchant');
        $definition->add('validationURL', new Type('string'), new NotBlank());

        $this->dataValidator->validate($data->all(), $definition);

        $merchantSession = $this->applePayService->validateMerchant($data->get('validationURL'), $request, $context);

        return new MerchantSessionResponse($merchantSession);
    }
}
