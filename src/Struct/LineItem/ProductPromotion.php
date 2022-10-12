<?php
declare(strict_types=1);

namespace Cko\Shopware6\Struct\LineItem;

use Shopware\Core\Framework\Struct\Struct;

class ProductPromotion extends Struct
{
    protected string $productId;

    protected PromotionCompositionCollection $promotions;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getPromotions(): PromotionCompositionCollection
    {
        return $this->promotions;
    }

    public function setPromotions(PromotionCompositionCollection $promotions): void
    {
        $this->promotions = $promotions;
    }
}
