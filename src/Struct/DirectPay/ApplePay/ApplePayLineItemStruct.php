<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\DirectPay\ApplePay;

use Cko\Shopware6\Struct\ApiStruct;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="checkout_com_apple_pay_line_item",
 *     required={"label", "amount", "type"}
 * )
 */
class ApplePayLineItemStruct extends ApiStruct
{
    /**
     * @OA\Property(
     *      description="Label"
     *  )
     */
    protected string $label;

    /**
     * @OA\Property(
     *      description="Amount"
     *  )
     */
    protected float $amount;

    /**
     * @OA\Property(
     *      description="Type"
     *  )
     */
    protected string $type;

    public function __construct(string $label, float $amount, string $type)
    {
        $this->label = $label;
        $this->amount = $amount;
        $this->type = $type;
    }
}
