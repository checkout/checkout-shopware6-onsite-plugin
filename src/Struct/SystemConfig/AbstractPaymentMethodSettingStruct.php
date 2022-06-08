<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\SystemConfig;

use Shopware\Core\Framework\Struct\Struct;

abstract class AbstractPaymentMethodSettingStruct extends Struct
{
    abstract public function getPaymentMethodType(): string;
}
