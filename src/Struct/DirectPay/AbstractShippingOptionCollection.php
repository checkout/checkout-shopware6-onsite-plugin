<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\DirectPay;

use Shopware\Core\Framework\Struct\Collection;

abstract class AbstractShippingOptionCollection extends Collection
{
    public function unshift(AbstractShippingOptionStruct $element): void
    {
        $this->validateType($element);

        array_unshift($this->elements, $element);
    }

    protected function getExpectedClass(): string
    {
        return AbstractShippingOptionStruct::class;
    }
}
