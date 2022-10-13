<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\DirectPay\Cart;

use Shopware\Core\Framework\Struct\Collection;

/**
 * @method DirectPayCartItemStruct[]    getIterator()
 * @method DirectPayCartItemStruct[]    getElements()
 * @method DirectPayCartItemStruct|null get(string $key)
 * @method DirectPayCartItemStruct|null first()
 * @method DirectPayCartItemStruct|null last()
 */
class DirectPayCartItemCollection extends Collection
{
    public function getTotalAmount(): float
    {
        $amount = 0;

        foreach ($this->getElements() as $item) {
            $amount += ($item->getQuantity() * $item->getPrice());
        }

        return $amount;
    }

    protected function getExpectedClass(): string
    {
        return DirectPayCartItemStruct::class;
    }
}
