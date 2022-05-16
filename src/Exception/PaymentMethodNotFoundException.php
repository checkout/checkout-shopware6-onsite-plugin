<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

class PaymentMethodNotFoundException extends CheckoutComException
{
    public function __construct(string $handlerIdentifier, array $parameters = [])
    {
        parent::__construct(sprintf('The payment method could not be: %s', $handlerIdentifier), $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_PAYMENT_METHOD_NOT_FOUND';
    }
}
