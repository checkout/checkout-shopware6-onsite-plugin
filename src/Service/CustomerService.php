<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use CheckoutCom\Shopware6\Event\CustomerSaveCardTokenEvent;
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
    public const CUSTOM_FIELDS_KEY_CHECKOUT_PAYMENTS = 'checkoutPayments';
    public const CUSTOM_FIELDS_KEY_CARD_TOKEN = 'cardToken';

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
        $customFields = $customer->getCustomFields() ?? [];

        // Store the card token in the custom fields
        $customFields[self::CUSTOM_FIELDS_KEY_CHECKOUT_PAYMENTS][self::CUSTOM_FIELDS_KEY_CARD_TOKEN] = $cardToken;

        // We update the customer with the new custom fields
        $customer->setCustomFields($customFields);

        // Dispatch event before update to repository
        $this->eventDispatcher->dispatch(new CustomerSaveCardTokenEvent($customer, $cardToken, $context));

        $this->logger->debug('Setting Card Token', [
            'customerId' => $customer->getId(),
            'customFields' => $customFields,
        ]);

        return $this->customerRepository->update([[
            'id' => $customer->getId(),
            'customFields' => $customer->getCustomFields(),
        ]], $context->getContext());
    }

    public function getCustomer(string $customerId, SalesChannelContext $context): CustomerEntity
    {
        $criteria = new Criteria([$customerId]);

        /** @var CustomerEntity|null $customer */
        $customer = $this->customerRepository->search($criteria, $context->getContext())->first();

        if (!$customer instanceof CustomerEntity) {
            throw new CustomerNotFoundByIdException($customerId);
        }

        return $customer;
    }
}
