<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Service\CustomerService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Struct\CustomFields\CustomerCustomFieldsStruct;
use CheckoutCom\Shopware6\Tests\Fakes\FakeEntityRepository;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundByIdException;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomerServiceTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    private FakeEntityRepository $customerRepository;

    private CustomerService $customerService;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->customerRepository = new FakeEntityRepository(new CustomerDefinition());
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->customerService = new CustomerService(
            $this->createMock(LoggerService::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->customerRepository,
        );
    }

    public function testSetCardToken(): void
    {
        $cardToken = 'any card token string';
        $customer = $this->getCustomerEntity('', '', '');

        // Get existing custom fields
        $checkoutCustomerCustomFields = CustomerService::getCheckoutCustomerCustomFields($customer);
        $checkoutCustomerCustomFields->setCardToken($cardToken);

        $event = $this->createMock(EntityWrittenContainerEvent::class);
        $this->customerRepository->entityWrittenContainerEvents[] = $event;

        $this->customerService->setCardToken($customer, $cardToken, $this->salesChannelContext);

        static::assertNotEmpty($this->customerRepository->data);
        static::assertArrayHasKey('customFields', $this->customerRepository->data[0][0]);
        static::assertArrayHasKey(CustomerService::CHECKOUT_CUSTOM_FIELDS, $this->customerRepository->data[0][0]['customFields']);
        static::assertSame($checkoutCustomerCustomFields->jsonSerialize(), $this->customerRepository->data[0][0]['customFields'][CustomerService::CHECKOUT_CUSTOM_FIELDS]);
    }

    /**
     * @dataProvider checkoutCustomerCustomFieldsProvider
     */
    public function testCheckoutCustomerCustomFields(
        ?string $expectedCardToken,
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

        $customer = $this->customerService->getCustomer($customerId, $this->salesChannelContext);
        $checkoutCustomFields = CustomerService::getCheckoutCustomerCustomFields($customer);

        static::assertSame($customerId, $customer->getId());
        static::assertSame($customFields, $customer->getCustomFields());
        static::assertInstanceOf(CustomerCustomFieldsStruct::class, $checkoutCustomFields);
        static::assertSame($expectedCardToken, $checkoutCustomFields->getCardToken());
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

        $this->customerService->getCustomer('NotFound', $this->salesChannelContext);
    }

    public function checkoutCustomerCustomFieldsProvider(): array
    {
        return [
            'Test Customer Full Fields' => [
                'expectedCardToken' => 'card 123',
                '123',
                'customFields' => [
                    CustomerService::CHECKOUT_CUSTOM_FIELDS => [
                        'cardToken' => 'card 123',
                        'anyOtherFields' => 'Other value',
                    ],
                ],
            ],
            'Test Customer empty checkout custom fields' => [
                'expectedCardToken' => null,
                '234',
                'customFields' => [
                    CustomerService::CHECKOUT_CUSTOM_FIELDS => [
                    ],
                ],
            ],
            'Test Customer null checkout custom fields' => [
                'expectedCardToken' => null,
                '234',
                'customFields' => [
                    CustomerService::CHECKOUT_CUSTOM_FIELDS => null,
                ],
            ],
            'Test Customer empty custom fields' => [
                'expectedCardToken' => null,
                '345',
                'customFields' => [
                ],
            ],
            'Test Customer null custom fields' => [
                'expectedCardToken' => null,
                '456',
                'customFields' => null,
            ],
            'Test Customer not exists custom fields' => [
                'expectedCardToken' => null,
                '567',
            ],
        ];
    }
}
