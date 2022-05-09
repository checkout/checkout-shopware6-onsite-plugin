<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\SystemConfig;

use CheckoutCom\Shopware6\Handler\Method\GooglePayHandler;

class GooglePaySettingStruct extends AbstractPaymentMethodSettingStruct
{
    protected ?string $merchantId = null;

    public function getPaymentMethodType(): string
    {
        return GooglePayHandler::getPaymentMethodType();
    }

    public function getMerchantId(): ?string
    {
        return $this->merchantId;
    }

    public function setMerchantId(?string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }
}
