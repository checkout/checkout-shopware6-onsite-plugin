<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\LineItem;

use Shopware\Core\Framework\Struct\Collection;

/**
 * @method PromotionComposition[]    getIterator()
 * @method PromotionComposition[]    getElements()
 * @method PromotionComposition|null get(string $key)
 * @method PromotionComposition|null first()
 * @method PromotionComposition|null last()
 */
class PromotionCompositionCollection extends Collection
{
    /**
     * @return array<PromotionCompositionCollection>
     */
    public function groupByRemainingQuantity(): array
    {
        $groups = [];

        foreach ($this->getElements() as $element) {
            if (\array_key_exists($element->getRemainingQuantity(), $groups)) {
                $collections = $groups[$element->getRemainingQuantity()];
            } else {
                $collections = $this->createNew();
            }

            $collections->add($element);
            $groups[$element->getRemainingQuantity()] = $collections;
        }

        return $groups;
    }

    protected function getExpectedClass(): ?string
    {
        return PromotionComposition::class;
    }
}
