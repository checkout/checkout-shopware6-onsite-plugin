<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Exception\SalutationNotFoundException;
use CheckoutCom\Shopware6\Handler\Method\CardPaymentHandler;
use CheckoutCom\Shopware6\Service\CustomerService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\PaymentSource;
use CheckoutCom\Shopware6\Struct\Customer\RegisterAndLoginGuestStruct;
use CheckoutCom\Shopware6\Struct\Request\RegisterAndLoginGuestRequest;
use CheckoutCom\Shopware6\Tests\Fakes\FakeEntityRepository;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\CustomerResponse;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CustomerServiceTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    /**
     * @var MockObject|AbstractRegisterRoute
     */
    private $registerRoute;

    private FakeEntityRepository $customerRepository;

    private FakeEntityRepository $salutationRepository;

    /**
     * @var MockObject|SystemConfigService
     */
    private $systemConfigService;

    private CustomerService $customerService;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->registerRoute = $this->createMock(AbstractRegisterRoute::class);
        $this->customerRepository = new FakeEntityRepository(new CustomerDefinition());
        $this->salutationRepository = new FakeEntityRepository(new SalutationDefinition());
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->customerService = new CustomerService(
            $this->registerRoute,
            $this->createMock(LoggerService::class),
            $this->systemConfigService,
            $this->customerRepository,
            $this->salutationRepository,
        );
    }

    /**
     * @dataProvider checkoutCustomerCustomFieldsProvider
     */
    public function testCheckoutCustomerCustomFields(
        string $customerId,
        ?array $customFields = null
    ): void {
        $mockCustomer = $this->createConfiguredMock(CustomerEntity::class, [
            'getId' => $customerId,
            'getCustomFields' => $customFields,
        ]);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $mockCustomer,
        ]);

        $this->customerRepository->entitySearchResults[] = $search;

        $customer = $this->customerService->getCustomer($customerId, $this->salesChannelContext->getContext());

        static::assertSame($customerId, $customer->getId());
        static::assertSame($customFields, $customer->getCustomFields());
    }

    /**
     * Test if the customer does not exist
     */
    public function testCustomerDoesntExists(): void
    {
        $searchResult = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => null,
        ]);

        $this->customerRepository->entitySearchResults[] = $searchResult;

        static::expectException(CustomerNotFoundByIdException::class);

        $this->customerService->getCustomer('NotFound', $this->salesChannelContext->getContext());
    }

    /**
     * @dataProvider registerAndLoginCustomerProvider
     */
    public function testRegisterAndLoginCustomer(bool $hasConfig, bool $hasDomains, bool $hasFirstDomain, bool $hasContextToken): void
    {
        $registerAndLoginCustomerRequest = new RegisterAndLoginGuestRequest(
            'foo',
            'foo',
            'foo',
            'foo',
            'foo',
            'foo',
            'foo',
            'foo',
            null,
            $this->createMock(CountryEntity::class)
        );

        $salutation = $this->createMock(SalutationEntity::class);

        $this->systemConfigService->method('getString')
            ->willReturn($hasConfig ? 'any url' : '');

        $this->testGetNotSpecifiedSalutation(true);

        $salesChannelEntity = $this->createMock(SalesChannelEntity::class);

        if (!$hasConfig) {
            if ($hasDomains) {
                $domains = new SalesChannelDomainCollection();
                if ($hasFirstDomain) {
                    $domain = new SalesChannelDomainEntity();
                    $domain->setUrl('foo');
                    $domain->setId('foo');
                    $domains->add($domain);
                } else {
                    static::expectException(SalesChannelDomainNotFoundException::class);
                }

                $salesChannelEntity->method('getDomains')->willReturn($domains);
            } else {
                static::expectException(SalesChannelDomainNotFoundException::class);
            }
        }

        $salesChannel = $this->createConfiguredMock(SalesChannelContext::class, [
            'getSalesChannel' => $salesChannelEntity,
        ]);

        if ($hasConfig || $hasFirstDomain) {
            $customerResponse = new CustomerResponse($this->createMock(CustomerEntity::class));

            if ($hasContextToken) {
                $customerResponse->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, 'foo');
            } else {
                static::expectException(MissingRequestParameterException::class);
            }
            $this->registerRoute->method('register')
                ->willReturn($customerResponse);
        }

        $expect = $this->customerService->registerAndLoginCustomer($registerAndLoginCustomerRequest, $salutation, $salesChannel);
        static::assertInstanceOf(RegisterAndLoginGuestStruct::class, $expect);
    }

    /**
     * @dataProvider getNotSpecifiedSalutationProvider
     */
    public function testGetNotSpecifiedSalutation(bool $expectFound): void
    {
        if (!$expectFound) {
            static::expectException(SalutationNotFoundException::class);
        }

        $mock = $this->createMock(SalutationEntity::class);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $expectFound ? $mock : null,
        ]);

        $this->salutationRepository->entitySearchResults[] = $search;

        $salutation = $this->customerService->getNotSpecifiedSalutation($this->salesChannelContext->getContext());

        static::assertInstanceOf(SalutationEntity::class, $salutation);
    }

    public function testRemoveCustomerSource(): void
    {
        $customer = $this->getCustomer();

        $event = $this->createMock(EntityWrittenContainerEvent::class);
        $this->customerRepository->entityWrittenContainerEvents[] = $event;

        $this->customerService->removeCustomerSource('foo', $customer, $this->salesChannelContext);
        static::assertNotEmpty($this->customerRepository->data);
    }

    public function testSaveCustomerSource(): void
    {
        $paymentSource = (new PaymentSource())->assign([
            'id' => 'foo',
            'type' => CardPaymentHandler::getPaymentMethodType(),
        ]);

        $mockCustomer = $this->createConfiguredMock(CustomerEntity::class, [
            'getId' => 'foo',
        ]);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $mockCustomer,
        ]);

        $this->customerRepository->entitySearchResults[] = $search;

        $event = $this->createMock(EntityWrittenContainerEvent::class);
        $this->customerRepository->entityWrittenContainerEvents[] = $event;

        $this->customerService->saveCustomerSource('foo', $paymentSource, $this->salesChannelContext);
        static::assertNotEmpty($this->customerRepository->data);
    }

    public function testUpdateCheckoutCustomFields(): void
    {
        $customer = $this->getCustomer();

        // Get existing custom fields
        $checkoutSourceCustomFields = CustomerService::getCheckoutSourceCustomFields($customer);

        $event = $this->createMock(EntityWrittenContainerEvent::class);
        $this->customerRepository->entityWrittenContainerEvents[] = $event;

        $this->customerService->updateCheckoutCustomFields($customer, $checkoutSourceCustomFields, $this->salesChannelContext);

        static::assertNotEmpty($this->customerRepository->data);
        static::assertArrayHasKey('customFields', $this->customerRepository->data[0][0]);
        static::assertArrayHasKey(CustomerService::CHECKOUT_SOURCE_CUSTOM_FIELDS, $this->customerRepository->data[0][0]['customFields']);
    }

    public function checkoutCustomerCustomFieldsProvider(): array
    {
        return [
            'Test Customer null checkout custom fields' => [
                '234',
                'customFields' => [
                    'test' => 'anyCustomFields',
                ],
            ],
            'Test Customer empty custom fields' => [
                '345',
                'customFields' => [
                ],
            ],
            'Test Customer null custom fields' => [
                '456',
                'customFields' => null,
            ],
            'Test Customer not exists custom fields' => [
                '567',
            ],
        ];
    }

    public function getNotSpecifiedSalutationProvider(): array
    {
        return [
            'Test did not find a salutation' => [
                false,
            ],
            'Test found a salutation' => [
                true,
            ],
        ];
    }

    public function registerAndLoginCustomerProvider(): array
    {
        return [
            'Test has not system domain config and do not have domains collection expect throw exception' => [
                false,
                false,
                false,
                false,
            ],
            'Test has not system domain config and have domains collection but do not have first domain expect throw exception' => [
                false,
                true,
                false,
                false,
            ],
            'Test has not system domain config and have domains collection and have first domain but empty response token expect throw exception' => [
                false,
                true,
                true,
                false,
            ],
            'Test has system domain config but empty response token expect throw exception' => [
                true,
                true,
                false,
                false,
            ],
            'Test has url and has response token expect success' => [
                true,
                true,
                true,
                true,
            ],
        ];
    }

    private function getCustomer(): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setId('foo');
        $customer->setCustomFields([
            CustomerService::CHECKOUT_SOURCE_CUSTOM_FIELDS => [
                CardPaymentHandler::getPaymentMethodType() => [
                    ['id' => 'foo'],
                    ['id' => 'bar'],
                ],
                'foo' => 'bar',
            ],
        ]);

        return $customer;
    }
}
