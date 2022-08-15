<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\CustomFields;

use Shopware\Core\Framework\Struct\Struct;

class OrderCustomFieldsStruct extends Struct
{
    protected ?string $checkoutPaymentId = null;

    protected ?bool $isRefundedFromHub = false;

    protected ?string $lastCheckoutActionId = null;

    protected string $checkoutReturnUrl = '';

    protected string $transactionReturnUrl = '';

    protected bool $shouldSaveSource = false;

    protected bool $manualCapture = false;

    public function __construct(?string $checkoutPaymentId = null, string $checkoutReturnUrl = '', string $transactionReturnUrl = '')
    {
        $this->checkoutPaymentId = $checkoutPaymentId;
        $this->checkoutReturnUrl = $checkoutReturnUrl;
        $this->transactionReturnUrl = $transactionReturnUrl;
    }

    public function getCheckoutPaymentId(): ?string
    {
        return $this->checkoutPaymentId;
    }

    public function setCheckoutPaymentId(?string $checkoutPaymentId): void
    {
        $this->checkoutPaymentId = $checkoutPaymentId;
    }

    public function getLastCheckoutActionId(): ?string
    {
        return $this->lastCheckoutActionId;
    }

    public function setLastCheckoutActionId(?string $lastCheckoutActionId): void
    {
        $this->lastCheckoutActionId = $lastCheckoutActionId;
    }

    public function isRefundedFromHub(): ?bool
    {
        return $this->isRefundedFromHub;
    }

    public function setIsRefundedFromHub(?bool $isRefundedFromHub): void
    {
        $this->isRefundedFromHub = $isRefundedFromHub;
    }

    public function getCheckoutReturnUrl(): string
    {
        // Not all payment methods have a return url, so we still use the shopware transaction return url
        if (empty($this->checkoutReturnUrl)) {
            return $this->getTransactionReturnUrl();
        }

        return $this->checkoutReturnUrl;
    }

    public function setCheckoutReturnUrl(?string $checkoutReturnUrl): void
    {
        $this->checkoutReturnUrl = $checkoutReturnUrl ?? '';
    }

    public function getTransactionReturnUrl(): string
    {
        return $this->transactionReturnUrl;
    }

    public function setTransactionReturnUrl(string $transactionReturnUrl): void
    {
        $this->transactionReturnUrl = $transactionReturnUrl;
    }

    public function isShouldSaveSource(): bool
    {
        return $this->shouldSaveSource;
    }

    public function setShouldSaveSource(bool $shouldSaveSource): void
    {
        $this->shouldSaveSource = $shouldSaveSource;
    }

    public function canManualCapture(): bool
    {
        return $this->manualCapture;
    }

    public function setManualCapture(bool $manualCapture): void
    {
        $this->manualCapture = $manualCapture;
    }
}
