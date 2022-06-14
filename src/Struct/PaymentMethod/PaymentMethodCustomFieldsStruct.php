<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Struct;

class PaymentMethodCustomFieldsStruct extends Struct
{
    protected ?string $methodType;

    public function getMethodType(): ?string
    {
        return $this->methodType;
    }

    public function setMethodType(?string $methodType): void
    {
        $this->methodType = $methodType;
    }
}
