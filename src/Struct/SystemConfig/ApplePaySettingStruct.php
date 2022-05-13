<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\SystemConfig;

use Shopware\Core\Framework\Struct\Struct;

class ApplePaySettingStruct extends Struct
{
    protected ?string $merchantId = null;

    protected ?string $domainMediaId = null;

    protected ?string $keyMediaId = null;

    protected ?string $pemMediaId = null;

    public function getMerchantId(): ?string
    {
        return $this->merchantId;
    }

    public function setMerchantId(?string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    public function getDomainMediaId(): ?string
    {
        return $this->domainMediaId;
    }

    public function setDomainMediaId(?string $domainMediaId): void
    {
        $this->domainMediaId = $domainMediaId;
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
}
