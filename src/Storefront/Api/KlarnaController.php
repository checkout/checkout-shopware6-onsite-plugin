<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Service\CheckoutApi\Apm\CheckoutKlarnaService;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderService;
use CheckoutCom\Shopware6\Struct\LineItemTotalPrice;
use CheckoutCom\Shopware6\Struct\Response\CreditSessionResponse;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use Throwable;

/**
 * This controller handles all relative to Klarna payment method such as:
 * Create session
 *
 * @RouteScope(scopes={"store-api"})
 */
class KlarnaController extends AbstractController
{
    private DataValidator $dataValidator;

    private LoggerInterface $logger;

    private AbstractOrderService $orderService;

    private CheckoutKlarnaService $checkoutKlarnaService;

    public function __construct(
        DataValidator $dataValidator,
        LoggerInterface $logger,
        AbstractOrderService $orderService,
        CheckoutKlarnaService $checkoutKlarnaService
    ) {
        $this->dataValidator = $dataValidator;
        $this->logger = $logger;
        $this->orderService = $orderService;
        $this->checkoutKlarnaService = $checkoutKlarnaService;
    }

    /**
     * @OA\Post (
     *      path="/checkout-com/klarna/credit-sessions",
     *      summary="Create a Klarna session for your customer",
     *      description="When the customer reaches your checkout page, create a session with Klarna.",
     *      operationId="checkoutComKlarnaCreateCreditSession",
     *      tags={"Store API", "CheckoutCom"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="orderId",
     *                  description="Create Klarna session with orderId",
     *                  type="string"
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Successfully create a Klarna session",
     *          @OA\JsonContent(ref="#/components/schemas/checkout_com_klarna_credit_session_response")
     *     )
     * )
     * @Route("/store-api/checkout-com/klarna/credit-sessions", name="store-api.checkout-com.klarna.credit-sessions", methods={"POST"})
     */
    public function createCreditSession(RequestDataBag $data, Cart $cart, SalesChannelContext $context): CreditSessionResponse
    {
        $this->logger->info('Klarna: Start create the credit sessions');

        try {
            $dataValidation = $this->getCreateCreditSessionValidation();
            $this->dataValidator->validate($data->all(), $dataValidation);

            $response = $this->checkoutKlarnaService->createCreditSession(
                $this->getLineItemTotalPrice($data, $cart, $context),
                $context
            );

            return new CreditSessionResponse(true, $response);
        } catch (Throwable $e) {
            $this->logger->error('Klarna: Error creating the credit session', ['exception' => $e->getMessage()]);

            throw new CheckoutComException('Klarna: Error creating the credit session');
        }
    }

    private function getLineItemTotalPrice(RequestDataBag $data, Cart $cart, SalesChannelContext $context): LineItemTotalPrice
    {
        $orderId = $data->get('orderId');
        if (empty($orderId)) {
            return CheckoutComUtil::buildLineItemTotalPrice($cart);
        }

        $order = $this->orderService->getOrder(
            $context->getContext(),
            $orderId,
            [
                'lineItems',
                'deliveries.shippingMethod',
            ]
        );

        return CheckoutComUtil::buildLineItemTotalPrice($order);
    }

    private function getCreateCreditSessionValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.klarna.create_credit_session');

        $definition->add('orderId', new Optional(new Type('string')));

        return $definition;
    }
}
