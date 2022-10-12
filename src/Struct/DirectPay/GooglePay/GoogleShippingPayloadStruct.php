<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\DirectPay\GooglePay;

use Cko\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;
use Cko\Shopware6\Struct\DirectPay\AbstractShippingPayloadStruct;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="checkout_com_google_shipping_payload",
 *     required={"displayItems", "totalPrice"}
 * )
 */
class GoogleShippingPayloadStruct extends AbstractShippingPayloadStruct
{
    /**
     * @OA\Property(
     *      type="array",
     *      items={"$ref": "#/components/schemas/checkout_com_google_shipping_option"}
     *  ),
     */
    protected ?AbstractShippingOptionCollection $shippingOptions;

    /**
     * @OA\Property(
     *     type="array",
     *     items={"$ref": "#/components/schemas/checkout_com_google_pay_line_item"}
     *  ),
     */
    protected GooglePayLineItemCollection $displayItems;

    /**
     * @OA\Property(
     *      description="Total price"
     *  )
     */
    protected string $totalPrice;

    public function getShippingOptions(): ?AbstractShippingOptionCollection
    {
        return $this->shippingOptions;
    }

    public function setShippingOptions(?AbstractShippingOptionCollection $shippingOptions): void
    {
        $this->shippingOptions = $shippingOptions;
    }

    public function getDisplayItems(): GooglePayLineItemCollection
    {
        return $this->displayItems;
    }

    public function setDisplayItems(GooglePayLineItemCollection $displayItems): void
    {
        $this->displayItems = $displayItems;
    }

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }
}
