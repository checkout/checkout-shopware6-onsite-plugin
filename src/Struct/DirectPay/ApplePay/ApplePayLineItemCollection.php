<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\DirectPay\ApplePay;

use Shopware\Core\Framework\Struct\Collection;

class ApplePayLineItemCollection extends Collection
{
    protected function getExpectedClass(): string
    {
        return ApplePayLineItemStruct::class;
    }
}
