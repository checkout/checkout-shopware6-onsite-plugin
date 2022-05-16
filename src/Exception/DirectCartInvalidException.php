<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

class DirectCartInvalidException extends CheckoutComException
{
    public function __construct(string $cartToken, array $parameters = [])
    {
        parent::__construct(sprintf('The Direct cart invalid: %s', $cartToken), $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_DIRECT_CART_INVALID';
    }
}
