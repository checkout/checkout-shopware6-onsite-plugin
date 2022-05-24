<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Exception\CountryCodeNotFoundException;
use CheckoutCom\Shopware6\Exception\CountryStateNotFoundException;
use CheckoutCom\Shopware6\Service\CountryService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Fakes\FakeEntityRepository;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
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
}
