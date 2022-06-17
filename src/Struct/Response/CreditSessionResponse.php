<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Response;

use CheckoutCom\Shopware6\Struct\PaymentMethod\Klarna\CreditSessionStruct;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @OA\Schema(
 *     schema="checkout_com_klarna_credit_session_response"
 * )
 */
class CreditSessionResponse extends StoreApiResponse
{
    /**
     * @OA\Property(
     *      property="success",
     *      type="boolean",
     *      description="Success flag"
     *  ),
     *  @OA\Property(
     *      property="creditSession",
     *      description="Credit session response data",
     *      type="object",
     *      @OA\Property(
     *          property="session_id",
     *         description="Klarna session Id",
     *         type="string"
     *      ),
     *      @OA\Property(
     *          property="client_token",
     *          description="Klarna credit session client token",
     *          type="string"
     *      ),
     *      @OA\Property(
     *          property="payment_method_categories",
     *          description="Klarna payment methods",
     *          type="array"
     *          @OA\Items(
     *              @OA\Property(
     *                  property="name",
     *                  description="Payment method name",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="identifier",
     *                  description="Payment method identifier",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="asset_urls",
     *                  description="Payment method assert urls",
     *                  type="object",
     *                  @OA\Property(
     *                      property="descriptive",
     *                      description="Payment method descriptive url",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="standard",
     *                      description="Payment method name standard url",
     *                      type="string"
     *                  ),
     *             )
     *         )
     *     )
     * )
     *
     * @var ArrayStruct
     */
    protected $object;

    public function __construct(bool $success, ?CreditSessionStruct $creditSession = null)
    {
        parent::__construct(new ArrayStruct([
            'success' => $success,
            'creditSession' => empty($creditSession) ? null : $creditSession->toApiJson(),
        ], 'credit_session'));
    }

    public function getCreditSession(): ?CreditSessionStruct
    {
        return $this->object->get('creditSession');
    }
}
