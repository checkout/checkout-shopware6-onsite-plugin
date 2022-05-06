<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Response;

use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingPayloadStruct;
use Exception;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @OA\Schema(
 *     schema="checkout_com_direct_shipping_response"
 * )
 */
class DirectShippingResponse extends StoreApiResponse
{
    /**
     * @OA\Property(
     *      property="success",
     *      type="string",
     *      description="Success flag"
     *  ),
     * @OA\Property(
     *      property="shippingPayload",
     *      description="Shipping payload depending on payment method type",
     *      oneOf={
     *          @OA\Schema(ref="#/components/schemas/checkout_com_apple_shipping_payload"),
     *          @OA\Schema(ref="#/components/schemas/checkout_com_google_shipping_payload")
     *      }
     *  ),
     *
     * @var ArrayStruct
     */
    protected $object;

    /**
     * @throws Exception
     */
    public function __construct(bool $success, ?AbstractShippingPayloadStruct $shippingPayload = null)
    {
        parent::__construct(new ArrayStruct([
            'success' => $success,
            'shippingPayload' => empty($shippingPayload) ? null : $shippingPayload->toApiJson(),
        ], 'direct_shipping_method'));
    }

    public function getShippingPayload(): ?AbstractShippingPayloadStruct
    {
        return $this->object->get('shippingPayload');
    }
}
