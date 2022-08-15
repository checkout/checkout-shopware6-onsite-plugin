<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\SystemConfig;

use CheckoutCom\Shopware6\Handler\Method\CardPaymentHandler;

class CardPaymentSettingStruct extends AbstractPaymentMethodSettingStruct
{
    protected bool $manualCapture = false;

    public function getPaymentMethodType(): string
    {
        return CardPaymentHandler::getPaymentMethodType();
    }

    public function isManualCapture(): bool
    {
        return $this->manualCapture;
    }
}
