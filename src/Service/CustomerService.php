<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use CheckoutCom\Shopware6\Event\CustomerSaveCardTokenEvent;
use CheckoutCom\Shopware6\Struct\CustomFields\CustomerCustomFieldsStruct;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomerService
{
    public const CHECKOUT_CUSTOM_FIELDS = 'checkoutPayments';

    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    private EntityRepositoryInterface $customerRepository;

    public function __construct(LoggerInterface $logger, EventDispatcherInterface $eventDispatcher, EntityRepositoryInterface $customerRepository)
    {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Stores the credit card token in the custom fields of the customer.
     */
    public function setCardToken(CustomerEntity $customer, string $cardToken, SalesChannelContext $context): EntityWrittenContainerEvent
    {
        // Get existing custom fields
        $checkoutCustomerCustomFields = CustomerService::getCheckoutCustomerCustomFields($customer);
        $checkoutCustomerCustomFields->setCardToken($cardToken);

        // Dispatch event before update to repository
        $this->eventDispatcher->dispatch(new CustomerSaveCardTokenEvent($checkoutCustomerCustomFields, $cardToken, $context));

        $this->logger->debug('Setting Card Token', [
            'customerId' => $customer->getId(),
            'customFields' => [
                self::CHECKOUT_CUSTOM_FIELDS => $checkoutCustomerCustomFields->jsonSerialize(),
            ],
        ]);

        return $this->customerRepository->update([[
            'id' => $customer->getId(),
            'customFields' => [
                self::CHECKOUT_CUSTOM_FIELDS => $checkoutCustomerCustomFields->jsonSerialize(),
            ],
        ]], $context->getContext());
    }

    /**
     * Get custom fields of the customer for checkout.com
     */
    public static function getCheckoutCustomerCustomFields(CustomerEntity $customer): CustomerCustomFieldsStruct
    {
        $customFields = $customer->getCustomFields() ?? [];

        $checkoutCustomerCustomFields = new CustomerCustomFieldsStruct();
        $checkoutCustomerCustomFields->assign($customFields[self::CHECKOUT_CUSTOM_FIELDS] ?? []);

        return $checkoutCustomerCustomFields;
    }

    /**
     * Return a customer entity with address associations.
     */
    public function getCustomer(string $customerId, SalesChannelContext $context): CustomerEntity
    {
        $criteria = new Criteria([$customerId]);
        $criteria->addAssociations([
            'defaultBillingAddress.country',
            'defaultBillingAddress.countryState',
            'defaultShippingAddress.country',
            'defaultShippingAddress.countryState',
            'activeShippingAddress.country',
            'activeShippingAddress.countryState',
        ]);

        /** @var CustomerEntity|null $customer */
        $customer = $this->customerRepository->search($criteria, $context->getContext())->first();

        if (!$customer instanceof CustomerEntity) {
            $this->logger->critical(
                sprintf('Could not fetch customer with ID %s', $customerId)
            );

            throw new CustomerNotFoundByIdException($customerId);
        }

        return $customer;
    }
}
