<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct;

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

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function isSandboxMode(): bool
    {
        return $this->sandboxMode;
    }
}
