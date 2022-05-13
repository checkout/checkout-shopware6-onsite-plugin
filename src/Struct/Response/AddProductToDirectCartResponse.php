<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Response;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @OA\Schema(
 *     schema="checkout_com_add_product_to_direct_cart_response",
 * )
 */
class AddProductToDirectCartResponse extends StoreApiResponse
{
    /**
     * @OA\Property(
     *      property="success",
     *      type="boolean",
     *      description="Success flag"
     *  ),
     * @OA\Property(
     *      property="cartToken",
     *      type="string",
     *      description="New Cart token"
     *  ),
     *
     * @var ArrayStruct
     */
    protected $object;

    public function __construct(bool $success, string $cartToken)
    {
        parent::__construct(new ArrayStruct([
            'success' => $success,
            'cartToken' => $cartToken,
        ], 'add_product_to_direct_cart'));
    }

    public function getCartToken(): string
    {
        return $this->object->get('cartToken');
    }
}
