<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Exception\CheckoutComKlarnaException;
use CheckoutCom\Shopware6\Exception\CountryCodeNotFoundException;
use CheckoutCom\Shopware6\Exception\CountryStateNotFoundException;
use CheckoutCom\Shopware6\Service\CountryService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Fakes\FakeEntityRepository;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CountryServiceTest extends TestCase
{
    use ContextTrait;

    private FakeEntityRepository $countryRepository;

    private FakeEntityRepository $countryStateRepository;

    private CountryService $countryService;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->countryRepository = new FakeEntityRepository(new CustomerDefinition());
        $this->countryStateRepository = new FakeEntityRepository(new CountryStateDefinition());
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->countryService = new CountryService(
            $this->createMock(LoggerService::class),
            $this->countryRepository,
            $this->countryStateRepository,
        );
    }

    /**
     * @dataProvider getCountryByIsoCodeProvider
     */
    public function testGetCountryByIsoCode(?string $countryCode, bool $expectFound): void
    {
        if (!$expectFound) {
            static::expectException(CountryCodeNotFoundException::class);
        }

        $mock = $this->createMock(CountryEntity::class);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $expectFound ? $mock : null,
        ]);

        $this->countryRepository->entitySearchResults[] = $search;

        $country = $this->countryService->getCountryByIsoCode($countryCode, $this->salesChannelContext->getContext());

        static::assertInstanceOf(CountryEntity::class, $country);
    }

    /**
     * @dataProvider getCountryStateProvider
     */
    public function testGetCountryState(?string $stateCode, bool $expectFound): void
    {
        if (!$expectFound) {
            static::expectException(CountryStateNotFoundException::class);
        }

        $mock = $this->createMock(CountryStateEntity::class);

        $country = $this->createConfiguredMock(CountryEntity::class, [
            'getId' => 'foo',
            'getIso' => 'bar',
        ]);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $expectFound ? $mock : null,
        ]);

        $this->countryStateRepository->entitySearchResults[] = $search;

        $countryState = $this->countryService->getCountryState($stateCode, $country, $this->salesChannelContext->getContext());

        static::assertInstanceOf(CountryStateEntity::class, $countryState);
    }

    /**
     * @dataProvider getPurchaseCountryIsoCodeFromOrderProvider
     */
    public function testGetPurchaseCountryIsoCodeFromOrder(bool $hasOrderAddress, bool $hasCountry, bool $validIso): void
    {
        $order = $this->createMock(OrderEntity::class);

        if ($hasOrderAddress) {
            $country = $this->getCountry($hasCountry, $validIso);
            $orderAddress = $this->createMock(OrderAddressEntity::class, );
            if ($country) {
                $orderAddress->method('getCountry')
                    ->willReturn($country);
            }

            $order->expects(static::once())->method('getBillingAddress')
                ->willReturn($orderAddress);
        } else {
            $order->expects(static::once())->method('getBillingAddress');

            static::expectException(CheckoutComKlarnaException::class);
        }

        $expect = $this->countryService->getPurchaseCountryIsoCodeFromOrder($order);
        static::assertIsString($expect);
    }

    /**
     * @dataProvider getPurchaseCountryIsoCodeFromContextProvider
     */
    public function testGetPurchaseCountryIsoCodeFromContext(bool $hasCustomer, bool $hasCustomerAddress, bool $hasCountry, bool $validIso): void
    {
        $context = $this->createMock(SalesChannelContext::class);

        if ($hasCustomer) {
            $customer = $this->createMock(CustomerEntity::class, );
            if ($hasCustomerAddress) {
                $customerAddress = $this->createMock(CustomerAddressEntity::class);
                $country = $this->getCountry($hasCountry, $validIso);
                if ($country) {
                    $customerAddress->method('getCountry')
                        ->willReturn($country);
                }

                $customer->expects(static::once())->method('getDefaultBillingAddress')
                    ->willReturn($customerAddress);
            } else {
                $customer->expects(static::once())->method('getDefaultBillingAddress');
                static::expectException(CheckoutComKlarnaException::class);
            }

            $context->expects(static::once())->method('getCustomer')
                ->willReturn($customer);
        } else {
            $context->expects(static::once())->method('getCustomer');
            static::expectException(CheckoutComKlarnaException::class);
        }

        $expect = $this->countryService->getPurchaseCountryIsoCodeFromContext($context);
        static::assertIsString($expect);
    }

    /**
     * @dataProvider getCountryStateCodeProvider
     */
    public function testGetCountryStateCode(string $shortCode, ?string $expect): void
    {
        $countryState = new CountryStateEntity();
        $countryState->setId('foo');
        $countryState->setShortCode($shortCode);

        $result = $this->countryService->getCountryStateCode($countryState);

        static::assertSame($expect, $result);
    }

    public function getCountryByIsoCodeProvider(): array
    {
        return [
            'Test did not find an country' => [
                '123',
                false,
            ],
            'Test found an country' => [
                '12345',
                true,
            ],
        ];
    }

    public function getCountryStateProvider(): array
    {
        return [
            'Test did not find an country state' => [
                '123',
                false,
            ],
            'Test found an country state' => [
                '12345',
                true,
            ],
        ];
    }

    public function getPurchaseCountryIsoCodeFromOrderProvider(): array
    {
        return [
            'Test not found order address' => [
                false,
                false,
                false,
            ],
            'Test not found country entity' => [
                true,
                false,
                false,
            ],
            'Test invalid iso country code' => [
                true,
                true,
                false,
            ],
            'Test purchase success' => [
                true,
                true,
                true,
            ],
        ];
    }

    public function getPurchaseCountryIsoCodeFromContextProvider(): array
    {
        return [
            'Test not found customer' => [
                false,
                false,
                false,
                false,
            ],
            'Test not found customer address' => [
                true,
                false,
                false,
                false,
            ],
            'Test not found country entity' => [
                true,
                true,
                false,
                false,
            ],
            'Test invalid iso country code' => [
                true,
                true,
                true,
                false,
            ],
            'Test purchase success' => [
                true,
                true,
                true,
                true,
            ],
        ];
    }

    public function getCountryStateCodeProvider(): array
    {
        return [
            'Test wrong country data' => [
                '',
                '',
            ],
            'Test get data success' => [
                'DE-EN',
                'EN',
            ],
        ];
    }

    private function getCountry(bool $hasCountry, bool $validIso): ?CountryEntity
    {
        $country = null;
        if ($hasCountry) {
            $country = new CountryEntity();
            $country->setId('foo');
            if ($validIso) {
                $country->setIso('DE');
            } else {
                $country->setIso('Invalid iso');
                static::expectException(CheckoutComKlarnaException::class);
            }
        } else {
            static::expectException(CheckoutComKlarnaException::class);
        }

        return $country;
    }
}
