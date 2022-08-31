<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Service\CardPayment\AbstractCardPaymentService;
use CheckoutCom\Shopware6\Struct\Response\CardPaymentTokenResponse;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @RouteScope(scopes={"store-api"})
 */
class CardPaymentController extends AbstractCardPaymentController
{
    private DataValidator $dataValidator;

    private AbstractCardPaymentService $creditCardService;

    public function __construct(DataValidator $dataValidator, AbstractCardPaymentService $creditCardService)
    {
        $this->dataValidator = $dataValidator;
        $this->creditCardService = $creditCardService;
    }

    public function getDecorated(): AbstractCardPaymentController
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Create card payment token
     *
     * @OA\Post(
     *      path="/store-api/checkout-com/card-payment/token",
     *      summary="Create card payment token",
     *      description="Create card payment token",
     *      operationId="checkoutComCardPaymentToken",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              required={"number", "expiryMonth", "expiryYear", "cvv"},
     *              @OA\Property(
     *                  property="name",
     *                  description="The cardholder's name",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="number",
     *                  description="The card number",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="expiryMonth",
     *                  description="The expiry month of the card",
     *                  type="integer"
     *              ),
     *              @OA\Property(
     *                  property="expiryYear",
     *                  description="The expiry year of the card",
     *                  type="integer"
     *              ),
     *              @OA\Property(
     *                  property="cvv",
     *                  description="The card verification value/code. 3 digits, except for Amex (4 digits)",
     *                  type="string"
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Returns the token of card payment",
     *         @OA\JsonContent(ref="#/components/schemas/checkout_com_card_payment_token_response")
     *     )
     * )
     * @Route("/store-api/checkout-com/card-payment/token", name="store-api.checkout-com.card-payment.token", methods={"POST"})
     */
    public function createToken(SalesChannelContext $context, RequestDataBag $data): CardPaymentTokenResponse
    {
        $definition = $this->getCreateTokenValidation();
        $this->dataValidator->validate($data->all(), $definition);

        $token = $this->creditCardService->createToken($data, $context);

        return new CardPaymentTokenResponse($token->getToken());
    }

    public function getCreateTokenValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('card_payment_controller.token');
        $definition->add('name', new Type('string'), new Optional());
        $definition->add('number', new Type('string'), new NotBlank());
        $definition->add('expiryMonth', new Type('integer'), new NotBlank(), new GreaterThanOrEqual(1));
        $definition->add('expiryYear', new Type('integer'), new NotBlank());
        $definition->add('cvv', new Type('string'), new NotBlank());

        return $definition;
    }
}
