<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\DirectPay\ApplePay;

use Cko\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;

class AppleShippingOptionCollection extends AbstractShippingOptionCollection
{
    protected function getExpectedClass(): string
    {
        return AppleShippingOptionStruct::class;
    }
}
