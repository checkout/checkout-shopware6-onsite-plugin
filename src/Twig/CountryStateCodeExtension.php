<?php declare(strict_types=1);

namespace Cko\Shopware6\Twig;

use Cko\Shopware6\Service\CountryService;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CountryStateCodeExtension extends AbstractExtension
{
    private CountryService $countryService;

    public function __construct(CountryService $countryService)
    {
        $this->countryService = $countryService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('countryStateCode', [$this, 'getCountryStateCode']),
        ];
    }

    public function getCountryStateCode(?CountryStateEntity $countryStateEntity): ?string
    {
        if (!$countryStateEntity instanceof CountryStateEntity) {
            return null;
        }

        return $this->countryService->getCountryStateCode($countryStateEntity);
    }
}
