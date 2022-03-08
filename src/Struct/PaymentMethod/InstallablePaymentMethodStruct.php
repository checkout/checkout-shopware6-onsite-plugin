<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Struct;

class InstallablePaymentMethodStruct extends Struct
{
    protected DisplayNameTranslationCollection $displayName;

    protected string $handler;

    public function __construct(DisplayNameTranslationCollection $displayName, string $handler)
    {
        $this->displayName = $displayName;
        $this->handler = $handler;
    }

    public function getDisplayName(): DisplayNameTranslationCollection
    {
        return $this->displayName;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }
}
