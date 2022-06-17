<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

class CheckoutComKlarnaException extends CheckoutComException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_KLARNA_ERROR';
    }
}
