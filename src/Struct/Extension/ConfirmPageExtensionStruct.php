<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Extension;

use Shopware\Core\Framework\Struct\Struct;

class ConfirmPageExtensionStruct extends Struct
{
    protected string $frameUrl = '';

    protected string $publicKey = '';

    protected bool $sandboxMode = true;

    public function getFrameUrl(): string
    {
        return $this->frameUrl;
    }

    public function setFrameUrl(string $frameUrl): void
    {
        $this->frameUrl = $frameUrl;
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
