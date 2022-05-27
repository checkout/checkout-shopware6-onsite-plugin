<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use CheckoutCom\Shopware6\Exception\SalutationNotFoundException;
use CheckoutCom\Shopware6\Struct\Customer\RegisterAndLoginGuestStruct;
use CheckoutCom\Shopware6\Struct\Request\RegisterAndLoginGuestRequest;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CustomerService
{
    public const CHECKOUT_CUSTOM_FIELDS = 'checkoutPayments';
    public const SALUTATION_NOT_SPECIFIED = 'not_specified';

    private AbstractRegisterRoute $registerRoute;

    private LoggerInterface $logger;

    private SystemConfigService $systemConfigService;

    private EntityRepositoryInterface $customerRepository;

    private EntityRepositoryInterface $salutationRepository;

    public function __construct(
        AbstractRegisterRoute $registerRoute,
        LoggerInterface $logger,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $customerRepository,
        EntityRepositoryInterface $salutationRepository
    ) {
        $this->registerRoute = $registerRoute;
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
        $this->customerRepository = $customerRepository;
        $this->salutationRepository = $salutationRepository;
    }

    public function registerAndLoginCustomer(
        RegisterAndLoginGuestRequest $registerAndLoginGuestRequest,
        SalutationEntity $salutation,
        SalesChannelContext $context
    ): RegisterAndLoginGuestStruct {
        $dataBag = $this->getRegisterCustomerDataBag(
            $registerAndLoginGuestRequest,
            $salutation,
            $context
        );

        $response = $this->registerRoute->register($dataBag, $context, false);

        $this->logger->debug('Customer register and logged in');

        $newToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);

        if (empty($newToken)) {
            throw new MissingRequestParameterException(PlatformRequest::HEADER_CONTEXT_TOKEN);
        }

        $registerAndLoginCustomer = new RegisterAndLoginGuestStruct();
        $registerAndLoginCustomer->setCustomer($response->getCustomer());
        $registerAndLoginCustomer->setContextToken($newToken);

        return $registerAndLoginCustomer;
    }

    public function getNotSpecifiedSalutation(Context $context): SalutationEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', self::SALUTATION_NOT_SPECIFIED));

        // Get salutations
        $salutation = $this->salutationRepository->search($criteria, $context)->first();

        if (!$salutation instanceof SalutationEntity) {
            $this->logger->critical('Could not fetch not specified salutation');

            throw new SalutationNotFoundException(self::SALUTATION_NOT_SPECIFIED);
        }

        return $salutation;
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

    private function getRegisterCustomerDataBag(
        RegisterAndLoginGuestRequest $registerAndLoginGuestRequest,
        SalutationEntity $salutation,
        SalesChannelContext $context
    ): RequestDataBag {
        $salutationId = $salutation->getId();
        $countryState = $registerAndLoginGuestRequest->getCountryState();

        return new RequestDataBag([
            'guest' => true,
            'storefrontUrl' => $this->getStorefrontUrl($context),
            'salutationId' => $salutationId,
            'email' => $registerAndLoginGuestRequest->getEmail(),
            'firstName' => $registerAndLoginGuestRequest->getFirstName(),
            'lastName' => $registerAndLoginGuestRequest->getLastName(),
            'billingAddress' => [
                'firstName' => $registerAndLoginGuestRequest->getFirstName(),
                'lastName' => $registerAndLoginGuestRequest->getLastName(),
                'salutationId' => $salutationId,
                'street' => $registerAndLoginGuestRequest->getStreet(),
                'additionalAddressLine1' => $registerAndLoginGuestRequest->getAdditionalAddressLine1(),
                'zipcode' => $registerAndLoginGuestRequest->getZipCode(),
                'countryId' => $registerAndLoginGuestRequest->getCountry()->getId(),
                'phoneNumber' => $registerAndLoginGuestRequest->getPhoneNumber(),
                'city' => $registerAndLoginGuestRequest->getCity(),
                'countryStateId' => empty($countryState) ? null : $countryState->getId(),
            ],
            'acceptedDataProtection' => true,
        ]);
    }

    private function getStorefrontUrl(SalesChannelContext $salesChannelContext): string
    {
        $salesChannel = $salesChannelContext->getSalesChannel();
        $domainUrl = $this->systemConfigService->getString('core.loginRegistration.doubleOptInDomain', $salesChannel->getId());
        if (!empty($domainUrl)) {
            return $domainUrl;
        }

        $domains = $salesChannel->getDomains();
        if (!$domains instanceof SalesChannelDomainCollection) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        $domain = $domains->first();
        if (!$domain instanceof SalesChannelDomainEntity) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        return $domain->getUrl();
    }
}
