<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use CheckoutCom\Shopware6\Exception\SalutationNotFoundException;
use CheckoutCom\Shopware6\Handler\Method\CardPaymentHandler;
use CheckoutCom\Shopware6\Helper\Util;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\PaymentSource;
use CheckoutCom\Shopware6\Struct\Customer\CustomerSourceCollection;
use CheckoutCom\Shopware6\Struct\Customer\CustomerSourceCustomFieldsStruct;
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
    public const CHECKOUT_SOURCE_CUSTOM_FIELDS = 'checkoutComSource';
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

        $customer = $this->customerRepository->search($criteria, $context)->first();

        if (!$customer instanceof CustomerEntity) {
            $this->logger->critical(
                sprintf('Could not fetch customer with ID %s', $customerId)
            );

            throw new CustomerNotFoundByIdException($customerId);
        }

        return $customer;
    }

    public static function getCheckoutSourceCustomFields(CustomerEntity $customer): CustomerSourceCustomFieldsStruct
    {
        $customFields = $customer->getCustomFields() ?? [];
        $checkoutCustomFields = $customFields[self::CHECKOUT_SOURCE_CUSTOM_FIELDS] ?? [];

        $cardSourceTypes = self::getSupportSources();
        foreach ($cardSourceTypes as $cardSourceType) {
            if (!\array_key_exists($cardSourceType, $checkoutCustomFields)) {
                continue;
            }

            $sourceCollection = new CustomerSourceCollection();
            foreach ($checkoutCustomFields[$cardSourceType] as $source) {
                $sourceCollection->add(
                    (new PaymentSource())->assign($source)
                );
            }

            $checkoutCustomFields[$cardSourceType] = $sourceCollection;
        }

        return (new CustomerSourceCustomFieldsStruct())->assign($checkoutCustomFields);
    }

    public static function getSupportSources(): array
    {
        return [
            CardPaymentHandler::getPaymentMethodType(),
        ];
    }

    /**
     * Remove customer stored sources by sourceID from the customer custom fields.
     */
    public function removeCustomerSource(
        string $sourceId,
        CustomerEntity $customer,
        SalesChannelContext $salesChannelContext
    ): void {
        $customerSourceCustomFields = CustomerService::getCheckoutSourceCustomFields($customer);
        foreach ($customerSourceCustomFields->getVars() as $property => $cardSource) {
            if (!$cardSource instanceof CustomerSourceCollection) {
                continue;
            }

            $newCardSource = $cardSource->filter(function (PaymentSource $source) use ($sourceId) {
                return $source->getId() !== $sourceId;
            });

            $customerSourceCustomFields->assign([
                $property => $newCardSource,
            ]);
        }

        $this->updateCheckoutCustomFields($customer, $customerSourceCustomFields, $salesChannelContext);
    }

    /**
     * Save customer stored sources to the customer custom fields.
     */
    public function saveCustomerSource(
        string $customerId,
        PaymentSource $paymentSource,
        SalesChannelContext $salesChannelContext
    ): void {
        $sourceType = $paymentSource->getType();
        if (empty($sourceType)) {
            return;
        }

        if (!\in_array($sourceType, CustomerService::getSupportSources(), true)) {
            return;
        }
        $customer = $this->getCustomer($customerId, $salesChannelContext->getContext());
        $customerSourceCustomFields = CustomerService::getCheckoutSourceCustomFields($customer);
        $cardSource = $customerSourceCustomFields->getSourceByType($sourceType);
        if (!$cardSource instanceof CustomerSourceCollection) {
            // If the customer has no card source, we create a new instance of it
            $cardSource = new CustomerSourceCollection();
        }

        // Check if the source already exists
        if ($cardSource->hasFingerPrint($paymentSource->getFingerprint())) {
            return;
        }

        $cardSource->add($paymentSource);
        $customerSourceCustomFields->assign([
            $sourceType => $cardSource,
        ]);

        $this->updateCheckoutCustomFields($customer, $customerSourceCustomFields, $salesChannelContext);
    }

    /**
     * Update customer custom fields of the customer for checkout.com payments
     */
    public function updateCheckoutCustomFields(
        CustomerEntity $customer,
        CustomerSourceCustomFieldsStruct $customFields,
        SalesChannelContext $context
    ): void {
        $checkoutUpdateData = Util::serializeStruct($customFields);

        $this->logger->debug('Updating customer checkout custom fields', [
            'customerId' => $customer->getId(),
            'customFields' => [
                self::CHECKOUT_SOURCE_CUSTOM_FIELDS => $checkoutUpdateData,
            ],
        ]);

        $this->customerRepository->update([
            [
                'id' => $customer->getId(),
                'customFields' => [
                    self::CHECKOUT_SOURCE_CUSTOM_FIELDS => $checkoutUpdateData,
                ],
            ],
        ], $context->getContext());
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
