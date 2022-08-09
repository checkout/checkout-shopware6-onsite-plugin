<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\LineItem;

use Shopware\Core\Framework\Struct\Struct;

class LineItemPayload extends Struct
{
    public const LINE_ITEM_SHIPPING = 'shipping';
    public const LINE_ITEM_PRODUCT = 'product';
    public const LINE_ITEM_WEBHOOK = 'webhook';

    protected ?string $refundLineItemId = null;

    protected ?string $productId = null;

    protected ?string $type = null;

    public function getRefundLineItemId(): ?string
    {
        return $this->refundLineItemId;
    }

    public function setRefundLineItemId(?string $refundLineItemId): void
    {
        $this->refundLineItemId = $refundLineItemId;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }
}
