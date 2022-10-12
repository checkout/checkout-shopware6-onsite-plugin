<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\SystemConfig;

use Cko\Shopware6\Handler\Method\ApplePayHandler;

class ApplePaySettingStruct extends AbstractPaymentMethodSettingStruct
{
    protected ?string $merchantId = null;

    protected ?string $keyMediaId = null;

    protected ?string $pemMediaId = null;

    protected array $domainMedias = [];

    public function getPaymentMethodType(): string
    {
        return ApplePayHandler::getPaymentMethodType();
    }

    public function getMerchantId(): ?string
    {
        return $this->merchantId;
    }

    public function setMerchantId(?string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    public function getKeyMediaId(): ?string
    {
        return $this->keyMediaId;
    }

    public function setKeyMediaId(?string $keyMediaId): void
    {
        $this->keyMediaId = $keyMediaId;
    }

    public function getPemMediaId(): ?string
    {
        return $this->pemMediaId;
    }

    public function setPemMediaId(?string $pemMediaId): void
    {
        $this->pemMediaId = $pemMediaId;
    }

    public function getDomainMedias(): array
    {
        return $this->domainMedias;
    }

    public function setDomainMedias(array $domainMedias): void
    {
        $this->domainMedias = $domainMedias;
    }
}
