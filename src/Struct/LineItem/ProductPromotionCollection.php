<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\LineItem;

use Shopware\Core\Framework\Struct\Collection;

/**
 * @method ProductPromotion[]    getIterator()
 * @method ProductPromotion[]    getElements()
 * @method ProductPromotion|null get(string $key)
 * @method ProductPromotion|null first()
 * @method ProductPromotion|null last()
 */
class ProductPromotionCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return ProductPromotion::class;
    }
}
