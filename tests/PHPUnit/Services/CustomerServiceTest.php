<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Service\CustomerService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Fakes\FakeEntityRepository;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CustomerServiceTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    private FakeEntityRepository $customerRepository;

    private FakeEntityRepository $salutationRepository;

    private CustomerService $customerService;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->customerRepository = new FakeEntityRepository(new CustomerDefinition());
        $this->salutationRepository = new FakeEntityRepository(new SalutationDefinition());
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->customerService = new CustomerService(
            $this->createMock(AbstractRegisterRoute::class),
            $this->createMock(LoggerService::class),
            $this->createMock(SystemConfigService::class),
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
}
