<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\DirectPay\GooglePay;

use Shopware\Core\Framework\Struct\Collection;

class GooglePayLineItemCollection extends Collection
{
    protected function getExpectedClass(): string
    {
        return GooglePayLineItemStruct::class;
    }
}
