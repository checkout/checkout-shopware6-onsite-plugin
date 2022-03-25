<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Collection;

class InstallablePaymentMethodCollection extends Collection
{
    protected function getExpectedClass(): string
    {
        return InstallablePaymentMethodStruct::class;
    }
}
