<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentHandler;

class HandlerPrepareProcessStruct
{
    private string $redirectUrl;

    private ?string $checkoutComPaymentId;

    public function __construct(string $redirectUrl, ?string $checkoutComPaymentId)
    {
        $this->redirectUrl = $redirectUrl;
        $this->checkoutComPaymentId = $checkoutComPaymentId;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function getCheckoutComPaymentId(): ?string
    {
        return $this->checkoutComPaymentId;
    }

    public function setCheckoutComPaymentId(?string $checkoutComPaymentId): void
    {
        $this->checkoutComPaymentId = $checkoutComPaymentId;
    }
}
