<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct;

use CheckoutCom\Shopware6\Struct\CheckoutApi\Webhook;
use Shopware\Core\Framework\Struct\Struct;

class SettingStruct extends Struct
{
    public const ORDER_STATE_SKIP = 'checkout_com.skip';

    protected string $secretKey = '';

    protected string $publicKey = '';

    protected bool $sandboxMode = true;

    protected string $orderStateForPaidPayment = self::ORDER_STATE_SKIP;

    protected string $orderStateForFailedPayment = self::ORDER_STATE_SKIP;

    protected string $orderStateForAuthorizedPayment = self::ORDER_STATE_SKIP;

    protected string $orderStateForVoidedPayment = self::ORDER_STATE_SKIP;

    protected ?string $applePayMerchantId = null;

    protected ?string $applePayDomainMediaId = null;

    protected ?string $applePayKeyMediaId = null;

    protected ?string $applePayPemMediaId = null;

    protected ?string $googlePayMerchantId = null;

    protected ?Webhook $webhook = null;

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

    public function getOrderStateForPaidPayment(): string
    {
        return $this->orderStateForPaidPayment;
    }

    public function setOrderStateForPaidPayment(string $orderStateForPaidPayment): void
    {
        $this->orderStateForPaidPayment = $orderStateForPaidPayment;
    }

    public function getOrderStateForFailedPayment(): string
    {
        return $this->orderStateForFailedPayment;
    }

    public function setOrderStateForFailedPayment(string $orderStateForFailedPayment): void
    {
        $this->orderStateForFailedPayment = $orderStateForFailedPayment;
    }

    public function getOrderStateForAuthorizedPayment(): string
    {
        return $this->orderStateForAuthorizedPayment;
    }

    public function setOrderStateForAuthorizedPayment(string $orderStateForAuthorizedPayment): void
    {
        $this->orderStateForAuthorizedPayment = $orderStateForAuthorizedPayment;
    }

    public function getOrderStateForVoidedPayment(): string
    {
        return $this->orderStateForVoidedPayment;
    }

    public function setOrderStateForVoidedPayment(string $orderStateForVoidedPayment): void
    {
        $this->orderStateForVoidedPayment = $orderStateForVoidedPayment;
    }

    public function getApplePayMerchantId(): ?string
    {
        return $this->applePayMerchantId;
    }

    public function setApplePayMerchantId(?string $applePayMerchantId): void
    {
        $this->applePayMerchantId = $applePayMerchantId;
    }

    public function getApplePayDomainMediaId(): ?string
    {
        return $this->applePayDomainMediaId;
    }

    public function setApplePayDomainMediaId(?string $applePayDomainMediaId): void
    {
        $this->applePayDomainMediaId = $applePayDomainMediaId;
    }

    public function getApplePayKeyMediaId(): ?string
    {
        return $this->applePayKeyMediaId;
    }

    public function setApplePayKeyMediaId(?string $applePayKeyMediaId): void
    {
        $this->applePayKeyMediaId = $applePayKeyMediaId;
    }

    public function getApplePayPemMediaId(): ?string
    {
        return $this->applePayPemMediaId;
    }

    public function setApplePayPemMediaId(?string $applePayPemMediaId): void
    {
        $this->applePayPemMediaId = $applePayPemMediaId;
    }

    public function getGooglePayMerchantId(): ?string
    {
        return $this->googlePayMerchantId;
    }

    public function setGooglePayMerchantId(?string $googlePayMerchantId): void
    {
        $this->googlePayMerchantId = $googlePayMerchantId;
    }

    public function getWebhook(): ?Webhook
    {
        return $this->webhook;
    }

    public function setWebhook(Webhook $webhook): void
    {
        $this->webhook = $webhook;
    }
}
