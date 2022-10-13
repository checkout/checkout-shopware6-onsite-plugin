<?php declare(strict_types=1);

namespace Cko\Shopware6\Service;

use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class AddressService
{
    private EntityRepositoryInterface $orderAddressRepository;

    public function __construct(EntityRepositoryInterface $orderAddressRepository)
    {
        $this->orderAddressRepository = $orderAddressRepository;
    }

    public function getOrderAddress(string $addressId, Context $context): OrderAddressEntity
    {
        $criteria = new Criteria([$addressId]);
        $criteria->setLimit(1);
        $criteria->addAssociation('country');
        $criteria->addAssociation('countryState');

        $address = $this->orderAddressRepository->search($criteria, $context)->first();

        if (!$address instanceof OrderAddressEntity) {
            throw new AddressNotFoundException($addressId);
        }

        return $address;
    }
}
