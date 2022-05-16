<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use CheckoutCom\Shopware6\Exception\CountryCodeNotFoundException;
use CheckoutCom\Shopware6\Exception\CountryStateNotFoundException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;

class CountryService
{
    private LoggerInterface $logger;

    private EntityRepositoryInterface $countryRepository;

    private EntityRepositoryInterface $countryStateRepository;

    public function __construct(LoggerInterface $logger, EntityRepositoryInterface $countryRepository, EntityRepositoryInterface $countryStateRepository)
    {
        $this->logger = $logger;
        $this->countryRepository = $countryRepository;
        $this->countryStateRepository = $countryStateRepository;
    }

    public function getCountryByIsoCode(string $countryCode, Context $context): CountryEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', strtoupper($countryCode)));

        $country = $this->countryRepository->search($criteria, $context)->first();

        if (!$country instanceof CountryEntity) {
            $this->logger->critical(
                sprintf('Could not fetch country with country code: %s', $countryCode)
            );

            throw new CountryCodeNotFoundException($countryCode);
        }

        return $country;
    }

    public function getCountryState(string $stateCode, CountryEntity $country, Context $context): CountryStateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('countryId', $country->getId()));
        $criteria->addFilter(new EqualsFilter('shortCode', \sprintf('%s-%s', $country->getIso(), strtoupper($stateCode))));

        $countryState = $this->countryStateRepository->search($criteria, $context)->first();

        if (!$countryState instanceof CountryStateEntity) {
            $this->logger->critical(
                sprintf('Could not fetch country state with state code: %s', $stateCode)
            );

            throw new CountryStateNotFoundException($stateCode);
        }

        return $countryState;
    }
}
