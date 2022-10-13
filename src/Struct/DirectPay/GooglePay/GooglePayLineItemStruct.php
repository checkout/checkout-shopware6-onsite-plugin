<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\DirectPay\GooglePay;

use Cko\Shopware6\Struct\ApiStruct;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="checkout_com_google_pay_line_item",
 *     required={"type", "label", "price"}
 * )
 */
class GooglePayLineItemStruct extends ApiStruct
{
    /**
     * @OA\Property(
     *      description="Type"
     *  )
     */
    protected string $type;

    /**
     * @OA\Property(
     *      description="Label"
     *  )
     */
    protected string $label;

    /**
     * @OA\Property(
     *      description="Price"
     *  )
     */
    protected string $price;

    public function __construct(string $type, string $label, string $price)
    {
        $this->type = $type;
        $this->label = $label;
        $this->price = $price;
    }
}
