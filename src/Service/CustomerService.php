<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class CustomerService
{
    public const CHECKOUT_CUSTOM_FIELDS = 'checkoutPayments';

    private LoggerInterface $logger;

    private EntityRepositoryInterface $customerRepository;

    public function __construct(LoggerInterface $logger, EntityRepositoryInterface $customerRepository)
    {
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Return a customer entity with address associations.
     */
    public function getCustomer(string $customerId, Context $context): CustomerEntity
    {
        $criteria = new Criteria([$customerId]);
        $criteria->setLimit(1);
        $criteria->addAssociations([
            'defaultBillingAddress.country',
            'defaultBillingAddress.countryState',
            'defaultShippingAddress.country',
            'defaultShippingAddress.countryState',
            'activeShippingAddress.country',
            'activeShippingAddress.countryState',
        ]);

        $customer = $this->customerRepository->search($criteria, $context)->first();

        if (!$customer instanceof CustomerEntity) {
            $this->logger->critical(
                sprintf('Could not fetch customer with ID %s', $customerId)
            );

            throw new CustomerNotFoundByIdException($customerId);
        }

        return $customer;
    }
}
