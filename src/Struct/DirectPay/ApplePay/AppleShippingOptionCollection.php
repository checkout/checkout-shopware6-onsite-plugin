<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\DirectPay\ApplePay;

use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;

class AppleShippingOptionCollection extends AbstractShippingOptionCollection
{
    protected function getExpectedClass(): string
    {
        return AppleShippingOptionStruct::class;
    }
}
