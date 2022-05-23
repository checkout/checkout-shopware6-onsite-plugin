<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Twig;

use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CountryStateCodeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('countryStateCode', [$this, 'getCountryStateCode']),
        ];
    }

    public function getCountryStateCode(?CountryStateEntity $countryStateEntity): ?string
    {
        return CheckoutComUtil::getCountryStateCode($countryStateEntity);
    }
}
