<?php declare(strict_types=1);

namespace Cko\Shopware6\Exception;

class CheckoutInvalidTokenException extends CheckoutComException
{
    public function __construct(string $paymentType = '', array $parameters = [])
    {
        parent::__construct(sprintf('Invalid token for payment type: %s', $paymentType), $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_INVALID_REQUEST_TOKEN_NOT_FOUND';
    }
}
