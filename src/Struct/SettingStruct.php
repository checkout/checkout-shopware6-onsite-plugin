<?php declare(strict_types=1);

namespace CheckoutcomShopware\Struct;

use Shopware\Core\Framework\Struct\Struct;

class SettingStruct extends Struct
{
    protected string $secretKey;

    protected string $publicKey;

    protected bool $sandboxMode;

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    public function isSandboxMode(): bool
    {
        return $this->sandboxMode;
    }

    public function setSandboxMode(bool $sandboxMode): void
    {
        $this->sandboxMode = $sandboxMode;
    }
}
