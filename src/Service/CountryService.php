<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use CheckoutCom\Shopware6\Exception\CheckoutComKlarnaException;
use CheckoutCom\Shopware6\Exception\CountryCodeNotFoundException;
use CheckoutCom\Shopware6\Exception\CountryStateNotFoundException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

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

    public function getCountryIdsByListIsoCode(array $isoCodes, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('iso', $isoCodes));

        return $this->countryRepository->searchIds($criteria, $context)->getIds();
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

    public function getPurchaseCountryIsoCodeFromOrder(OrderEntity $order): string
    {
        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress instanceof OrderAddressEntity) {
            throw new CheckoutComKlarnaException('Can not get purchase country iso code. Order billing address is null');
        }

        return $this->getPurchaseCountryIsoCode($billingAddress);
    }

    public function getPurchaseCountryIsoCodeFromContext(SalesChannelContext $context): string
    {
        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw new CheckoutComKlarnaException('Can not get purchase country iso code. Customer is null');
        }

        $billingAddress = $customer->getDefaultBillingAddress();
        if (!$billingAddress instanceof CustomerAddressEntity) {
            throw new CheckoutComKlarnaException('Can not get purchase country iso code. Customer billing address is null');
        }

        return $this->getPurchaseCountryIsoCode($billingAddress);
    }

    public function getCountryStateCode(CountryStateEntity $countryStateEntity): ?string
    {
        $countryStateCode = $countryStateEntity->getShortCode();
        $countryStateData = explode('-', $countryStateCode);
        if (empty($countryStateData)) {
            return null;
        }

        return end($countryStateData);
    }

    /**
     * @param OrderAddressEntity|CustomerAddressEntity $billingAddress
     */
    private function getPurchaseCountryIsoCode(Entity $billingAddress): string
    {
        $country = $billingAddress->getCountry();
        if (!$country instanceof CountryEntity) {
            throw new CheckoutComKlarnaException('Can not get purchase country iso code. Billing address\'s country is null');
        }

        $isoCode = $country->getIso();
        if (!$isoCode || \strlen($isoCode) !== 2) {
            throw new CheckoutComKlarnaException('Can not get purchase country iso code. Invalid iso code is null');
        }

        return $isoCode;
    }
}
