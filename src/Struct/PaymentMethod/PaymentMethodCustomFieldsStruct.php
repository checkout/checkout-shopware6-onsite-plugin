<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Struct;

class PaymentMethodCustomFieldsStruct extends Struct
{
    protected ?string $methodType;

    protected ?bool $canManualVoid;

    public function getMethodType(): ?string
    {
        return $this->methodType;
    }

    public function setMethodType(?string $methodType): void
    {
        $this->methodType = $methodType;
    }

    public function getCanManualVoid(): ?bool
    {
        return $this->canManualVoid;
    }

    public function setCanManualVoid(?bool $canManualVoid): void
    {
        $this->canManualVoid = $canManualVoid;
    }
}
