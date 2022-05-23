<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod\Klarna;

use CheckoutCom\Shopware6\Struct\ApiStruct;

class CreditSessionStruct extends ApiStruct
{
    protected string $session_id;

    protected string $client_token;

    protected array $payment_method_categories;

    public function getSessionId(): string
    {
        return $this->session_id;
    }

    public function setSessionId(string $session_id): void
    {
        $this->session_id = $session_id;
    }

    public function getClientToken(): string
    {
        return $this->client_token;
    }

    public function setClientToken(string $client_token): void
    {
        $this->client_token = $client_token;
    }

    public function getPaymentMethodCategories(): array
    {
        return $this->payment_method_categories;
    }

    public function setPaymentMethodCategories(array $payment_method_categories): void
    {
        $this->payment_method_categories = $payment_method_categories;
    }
}
