<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\DirectPay\ApplePay;

use Cko\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;
use Cko\Shopware6\Struct\DirectPay\AbstractShippingPayloadStruct;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="checkout_com_apple_shipping_payload",
 *     required={"newTotal", "newLineItems"}
 * )
 */
class AppleShippingPayloadStruct extends AbstractShippingPayloadStruct
{
    /**
     * @OA\Property(
     *      type="array",
     *      items={"$ref": "#/components/schemas/checkout_com_apple_shipping_option"}
     *  ),
     */
    protected ?AbstractShippingOptionCollection $newShippingMethods;

    /**
     * @OA\Property(
     *      ref="#/components/schemas/checkout_com_apple_pay_line_item"
     *  ),
     */
    protected ApplePayLineItemStruct $newTotal;

    /**
     * @OA\Property(
     *     type="array",
     *     items={"$ref": "#/components/schemas/checkout_com_apple_pay_line_item"}
     *  ),
     */
    protected ApplePayLineItemCollection $newLineItems;

    public function getNewShippingMethods(): ?AbstractShippingOptionCollection
    {
        return $this->newShippingMethods;
    }

    public function setNewShippingMethods(?AbstractShippingOptionCollection $newShippingMethods): void
    {
        $this->newShippingMethods = $newShippingMethods;
    }

    public function getNewTotal(): ApplePayLineItemStruct
    {
        return $this->newTotal;
    }

    public function setNewTotal(ApplePayLineItemStruct $newTotal): void
    {
        $this->newTotal = $newTotal;
    }

    public function getNewLineItems(): ApplePayLineItemCollection
    {
        return $this->newLineItems;
    }

    public function setNewLineItems(ApplePayLineItemCollection $newLineItems): void
    {
        $this->newLineItems = $newLineItems;
    }
}
