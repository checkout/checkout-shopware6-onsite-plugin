<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

class SalutationNotFoundException extends CheckoutComException
{
    public function __construct(string $salutationKey, array $parameters = [])
    {
        parent::__construct(sprintf('The salutation could not be: %s', $salutationKey), $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_SALUTATION_NOT_FOUND';
    }
}
