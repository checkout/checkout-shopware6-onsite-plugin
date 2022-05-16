<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Struct;

class PaymentMethodCustomFieldsStruct extends Struct
{
    protected bool $isCheckout;

    protected ?string $methodType;

    public function __construct(bool $isCheckout = false, ?string $methodType = null)
    {
        $this->isCheckout = $isCheckout;
        $this->methodType = $methodType;
    }

    public function isCheckout(): bool
    {
        return $this->isCheckout;
    }

    public function setIsCheckout(bool $isCheckout): void
    {
        $this->isCheckout = $isCheckout;
    }

    public function getMethodType(): ?string
    {
        return $this->methodType;
    }

    public function setMethodType(?string $methodType): void
    {
        $this->methodType = $methodType;
    }
}