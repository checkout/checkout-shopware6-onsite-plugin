<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\CustomFields;

use Shopware\Core\Framework\Struct\Struct;

class OrderCustomFieldsStruct extends Struct
{
    protected ?string $checkoutPaymentId = null;

    protected string $checkoutReturnUrl = '';

    protected string $transactionReturnUrl = '';

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
}
