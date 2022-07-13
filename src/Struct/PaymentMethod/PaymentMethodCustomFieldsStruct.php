<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Struct;

class PaymentMethodCustomFieldsStruct extends Struct
{
    protected ?string $methodType;

    protected ?bool $canManualCapture;

    protected ?bool $canManualVoid;

    public function getMethodType(): ?string
    {
        return $this->methodType;
    }

    public function setMethodType(?string $methodType): void
    {
        $this->methodType = $methodType;
    }

    public function getCanManualCapture(): ?bool
    {
        return $this->canManualCapture;
    }

    public function setCanManualCapture(?bool $canManualCapture): void
    {
        $this->canManualCapture = $canManualCapture;
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
