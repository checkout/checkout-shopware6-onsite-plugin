<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Struct;

class InstalledPaymentMethodStruct extends Struct
{
    protected string $handler;

    protected bool $active;

    public function __construct(string $handler, bool $active)
    {
        $this->handler = $handler;
        $this->active = $active;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
