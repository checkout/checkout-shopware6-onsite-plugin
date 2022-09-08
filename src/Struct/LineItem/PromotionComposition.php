<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\LineItem;

use Shopware\Core\Framework\Struct\Struct;

class PromotionComposition extends Struct
{
    protected ?string $referencedId = null;

    protected ?string $label = null;

    protected ?string $lineItemId = null;

    protected float $discountPerQuantity = 0.0;

    protected int $remainingQuantity = 0;

    protected int $refundedQuantity = 0;

    public function getReferencedId(): ?string
    {
        return $this->referencedId;
    }

    public function setReferencedId(?string $referencedId): void
    {
        $this->referencedId = $referencedId;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getLineItemId(): ?string
    {
        return $this->lineItemId;
    }

    public function setLineItemId(?string $lineItemId): void
    {
        $this->lineItemId = $lineItemId;
    }

    public function getDiscountPerQuantity(): float
    {
        return $this->discountPerQuantity;
    }

    public function setDiscountPerQuantity(float $discountPerQuantity): void
    {
        $this->discountPerQuantity = $discountPerQuantity;
    }

    public function getRemainingQuantity(): int
    {
        return $this->remainingQuantity;
    }

    public function setRemainingQuantity(int $remainingQuantity): void
    {
        $this->remainingQuantity = $remainingQuantity;
    }

    public function getRefundedQuantity(): int
    {
        return $this->refundedQuantity;
    }

    public function setRefundedQuantity(int $refundedQuantity): void
    {
        $this->refundedQuantity = $refundedQuantity;
    }
}
