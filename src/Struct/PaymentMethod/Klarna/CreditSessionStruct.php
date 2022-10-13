<?php
declare(strict_types=1);

namespace Cko\Shopware6\Struct\PaymentMethod\Klarna;

use Cko\Shopware6\Struct\ApiStruct;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="checkout_com_klarna_credit_session"
 * )
 */
class CreditSessionStruct extends ApiStruct
{
    /**
     * @OA\Property(
     *      property="session_id",
     *  ),
     */
    protected string $session_id;

    /**
     * @OA\Property(
     *      property="client_token",
     *  ),
     */
    protected string $client_token;

    /**
     * @OA\Property(
     *      property="payment_method_categories",
     *      type="array",
     *      @OA\Items(
     *          @OA\Property(
     *              property="name",
     *              description="Payment method name",
     *              type="string"
     *          ),
     *          @OA\Property(
     *              property="identifier",
     *              description="Payment method identifier",
     *              type="string"
     *          ),
     *          @OA\Property(
     *              property="asset_urls",
     *              description="Payment method assert urls",
     *              type="object",
     *              @OA\Property(
     *                  property="descriptive",
     *                  description="Payment method descriptive url",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="standard",
     *                  description="Payment method name standard url",
     *                  type="string"
     *              ),
     *         )
     *     )
     *  ),
     */
    protected array $payment_method_categories;

    public function getSessionId(): string
    {
        return $this->session_id;
    }

    public function setSessionId(string $session_id): void
    {
        $this->session_id = $session_id;
    }

    public function getClientToken(): string
    {
        return $this->client_token;
    }

    public function setClientToken(string $client_token): void
    {
        $this->client_token = $client_token;
    }

    public function getPaymentMethodCategories(): array
    {
        return $this->payment_method_categories;
    }

    public function setPaymentMethodCategories(array $payment_method_categories): void
    {
        $this->payment_method_categories = $payment_method_categories;
    }
}
