<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\DirectPay\ApplePay;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @OA\Schema(
 *     schema="checkout_com_apple_pay_line_item",
 *     required={"label", "amount", "type"}
 * )
 */
class ApplePayLineItemStruct extends Struct
{
    /**
     *  @OA\Property(
     *      description="Label"
     *  )
     */
    protected string $label;

    /**
     *  @OA\Property(
     *      description="Amount"
     *  )
     */
    protected float $amount;

    /**
     *  @OA\Property(
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
