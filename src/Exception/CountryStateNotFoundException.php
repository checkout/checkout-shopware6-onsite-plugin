<?php declare(strict_types=1);

namespace Cko\Shopware6\Exception;

class CountryStateNotFoundException extends CheckoutComException
{
    public function __construct(string $countryState, array $parameters = [])
    {
        parent::__construct(sprintf('The country state could not be: %s', $countryState), $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_COUNTRY_STATE_NOT_FOUND';
    }
}
