<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\DirectPay\GooglePay;

use Cko\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;

class GoogleShippingOptionCollection extends AbstractShippingOptionCollection
{
    protected function getExpectedClass(): string
    {
        return GoogleShippingOptionStruct::class;
    }
}
