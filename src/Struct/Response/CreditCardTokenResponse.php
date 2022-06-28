<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Response;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @OA\Schema(
 *     schema="checkout_com_credit_card_token_response"
 * )
 */
class CreditCardTokenResponse extends StoreApiResponse
{
    /**
     * @OA\Property(
     *      property="success",
     *      type="string",
     *      description="Success flag"
     *  ),
     * @OA\Property(
     *      property="token",
     *      type="string",
     *      description="Credit card token",
     *  ),
     *
     * @var ArrayStruct
     */
    protected $object;

    public function __construct(string $token)
    {
        parent::__construct(new ArrayStruct([
            'success' => true,
            'token' => $token,
        ], 'credit_card_token_response'));
    }

    public function getToken(): string
    {
        return $this->object->get('token');
    }
}
