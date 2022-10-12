<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\Response;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @OA\Schema(
 *     schema="checkout_com_merchant_session_response"
 * )
 */
class MerchantSessionResponse extends StoreApiResponse
{
    /**
     * @OA\Property(
     *      property="success",
     *      type="string",
     *      description="Success flag"
     *  ),
     * @OA\Property(
     *      property="merchant",
     *      description="Apple Pay Merchant Session",
     *      type="object",
     *  ),
     *
     * @var ArrayStruct
     */
    protected $object;

    public function __construct(?array $merchantSession = null)
    {
        parent::__construct(new ArrayStruct([
            'success' => !empty($merchantSession),
            'merchant' => $merchantSession,
        ], 'merchant_session_response'));
    }

    public function getMerchantSession(): ArrayStruct
    {
        return $this->object;
    }
}
