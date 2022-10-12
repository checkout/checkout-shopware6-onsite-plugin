<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\SystemConfig;

use Cko\Shopware6\Handler\Method\CardPaymentHandler;

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
