<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\DirectPay\GooglePay;

use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingOptionStruct;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="checkout_com_google_shipping_option",
 * )
 */
class GoogleShippingOptionStruct extends AbstractShippingOptionStruct
{
    /**
     * @OA\Property(
     *      description="ID of the shipping option",
     *  ),
     */
    protected ?string $id = null;

    /**
     * @OA\Property(
     *      description="Label"
     *  ),
     */
    protected ?string $label = null;

    /**
     * @OA\Property(
     *      description="Description"
     *  ),
     */
    protected ?string $description = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
