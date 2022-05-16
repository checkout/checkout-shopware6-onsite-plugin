<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\DirectPay\ApplePay;

use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingOptionStruct;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="checkout_com_apple_shipping_option",
 * )
 */
class AppleShippingOptionStruct extends AbstractShippingOptionStruct
{
    /**
     * @OA\Property(
     *      description="Indentifier"
     *  ),
     */
    protected ?string $identifier = null;

    /**
     * @OA\Property(
     *      description="Label"
     *  ),
     */
    protected ?string $label = null;

    /**
     * @OA\Property(
     *      description="Amount"
     *  ),
     */
    protected ?float $amount = null;

    /**
     * @OA\Property(
     *      description="Detail"
     *  ),
     */
    protected ?string $detail = null;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): void
    {
        $this->detail = $detail;
    }
}
