<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Collection;

class InstalledPaymentMethodCollection extends Collection
{
    protected function getExpectedClass(): string
    {
        return InstalledPaymentMethodStruct::class;
    }
}
