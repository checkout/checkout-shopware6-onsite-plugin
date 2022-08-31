<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Extension;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @OA\Schema(
 *     schema="checkout_com_public_config",
 * )
 */
class PublicConfigStruct extends Struct
{
    /**
     * @OA\Property(
     *      description="Card Payment IFrame URL"
     *  )
     */
    protected string $frameUrl = '';

    /**
     * @OA\Property(
     *      description="Klarna CDN URL"
     *  )
     */
    protected string $klarnaCdnUrl = '';

    /**
     * @OA\Property(
     *      description="Checkout.com Public key"
     *  )
     */
    protected string $publicKey = '';

    /**
     * @OA\Property(
     *      description="Sandbox mode"
     *  )
     */
    protected bool $sandboxMode = true;

    /**
     * @OA\Property(
     *      description="Google Pay merchant ID"
     *  )
     */
    protected ?string $googlePayMerchantId = null;

    public function getFrameUrl(): string
    {
        return $this->frameUrl;
    }

    public function setFrameUrl(string $frameUrl): void
    {
        $this->frameUrl = $frameUrl;
    }

    public function getKlarnaCdnUrl(): string
    {
        return $this->klarnaCdnUrl;
    }

    public function setKlarnaCdnUrl(string $klarnaCdnUrl): void
    {
        $this->klarnaCdnUrl = $klarnaCdnUrl;
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

    public function getGooglePayMerchantId(): ?string
    {
        return $this->googlePayMerchantId;
    }

    public function setGooglePayMerchantId(?string $googlePayMerchantId): void
    {
        $this->googlePayMerchantId = $googlePayMerchantId;
    }
}
