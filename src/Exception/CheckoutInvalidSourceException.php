<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

class CheckoutInvalidSourceException extends CheckoutComException
{
    public function __construct(string $paymentType = '', array $parameters = [])
    {
        parent::__construct(sprintf('Invalid source for payment type: %s', $paymentType), $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_INVALID_SOURCE_DATA';
    }
}
