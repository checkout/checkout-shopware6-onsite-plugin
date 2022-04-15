<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\DirectPay\Cart;

use Shopware\Core\Framework\Struct\Collection;

class DirectPayCartItemCollection extends Collection
{
    public function getTotalAmount(): float
    {
        $amount = 0;

        /** @var DirectPayCartItemStruct $item */
        foreach ($this->elements as $item) {
            $amount += ($item->getQuantity() * $item->getPrice());
        }

        return $amount;
    }

    protected function getExpectedClass(): string
    {
        return DirectPayCartItemStruct::class;
    }
}
