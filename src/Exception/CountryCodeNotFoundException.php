<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

class CountryCodeNotFoundException extends CheckoutComException
{
    public function __construct(string $countryCode, array $parameters = [])
    {
        parent::__construct(sprintf('The country code could not be: %s', $countryCode), $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_COUNTRY_CODE_NOT_FOUND';
    }
}
