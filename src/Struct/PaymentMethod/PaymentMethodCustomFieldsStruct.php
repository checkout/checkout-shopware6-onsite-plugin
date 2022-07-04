<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Struct;

class PaymentMethodCustomFieldsStruct extends Struct
{
    protected ?string $methodType;

    protected ?bool $shouldManualCapture;

    protected ?bool $shouldManualVoid;

    public function getMethodType(): ?string
    {
        return $this->methodType;
    }

    public function setMethodType(?string $methodType): void
    {
        $this->methodType = $methodType;
    }

    public function getShouldManualCapture(): ?bool
    {
        return $this->shouldManualCapture;
    }

    public function setShouldManualCapture(?bool $shouldManualCapture): void
    {
        $this->shouldManualCapture = $shouldManualCapture;
    }

    public function getShouldManualVoid(): ?bool
    {
        return $this->shouldManualVoid;
    }

    public function setShouldManualVoid(?bool $shouldManualVoid): void
    {
        $this->shouldManualVoid = $shouldManualVoid;
    }
}
