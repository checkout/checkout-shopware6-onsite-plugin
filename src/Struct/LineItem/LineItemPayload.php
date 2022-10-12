<?php
declare(strict_types=1);

namespace Cko\Shopware6\Struct\LineItem;

use Shopware\Core\Framework\Struct\Struct;

class LineItemPayload extends Struct
{
    public const LINE_ITEM_SHIPPING = 'shipping';
    public const LINE_ITEM_PRODUCT = 'product';
    public const LINE_ITEM_WEBHOOK = 'webhook';
    public const LINE_ITEM_FIX_PRICE = 'fix_price';

    protected ?string $refundLineItemId = null;

    protected ?string $productId = null;

    protected ?string $type = null;

    protected ?array $discountCompositions = [];

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

    public function getDiscountCompositions(): ?array
    {
        return $this->discountCompositions;
    }

    public function setDiscountCompositions(?array $discountCompositions): void
    {
        $this->discountCompositions = $discountCompositions;
    }
}
