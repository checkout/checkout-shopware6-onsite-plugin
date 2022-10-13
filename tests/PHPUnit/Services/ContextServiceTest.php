<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Services;

use Cko\Shopware6\Exception\CheckoutComException;
use Cko\Shopware6\Service\ContextService;
use Cko\Shopware6\Service\LoggerService;
use Cko\Shopware6\Tests\Fakes\FakeEntityRepository;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class ContextServiceTest extends TestCase
{
    use ContextTrait;

    /**
     * @var MockObject|SalesChannelContextService
     */
    private $salesChannelContextService;

    private FakeEntityRepository $languageRepository;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    private ContextService $contextService;

    public function setUp(): void
    {
        $this->salesChannelContextService = $this->createMock(SalesChannelContextService::class);
        $this->languageRepository = new FakeEntityRepository(new LanguageDefinition());
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->contextService = new ContextService(
            $this->createMock(LoggerService::class),
            $this->languageRepository,
            $this->salesChannelContextService
        );
    }

    public function testGetSalesChannelContext(): void
    {
        $this->salesChannelContextService->expects(static::once())
            ->method('get')
            ->willReturn($this->salesChannelContext);

        $salesChannelContext = $this->contextService->getSalesChannelContext('foo', 'bar');

        static::assertInstanceOf(SalesChannelContext::class, $salesChannelContext);
    }

    public function testGetSalesChannelDomainOfNullDomain(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        static::expectException(CheckoutComException::class);
        $this->contextService->getSalesChannelDomain(null, $context);
    }

    public function testGetSalesChannelDomainOfEmptyDomainCollection(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $salesChannelEntity = $this->createConfiguredMock(SalesChannelEntity::class, [
            'getId' => 'foo',
            'getDomains' => null,
        ]);

        $context->expects(static::once())->method('getSalesChannel')
            ->willReturn($salesChannelEntity);

        static::expectException(SalesChannelDomainNotFoundException::class);
        $this->contextService->getSalesChannelDomain('foo', $context);
    }

    /**
     * @dataProvider getSalesChannelDomainProvider
     */
    public function testGetSalesChannelDomain(string $domainId): void
    {
        $domainEntityId = 'foo';
        $salesChannelDomainEntity = new SalesChannelDomainEntity();
        $salesChannelDomainEntity->setId($domainEntityId);

        $domains = new SalesChannelDomainCollection([$salesChannelDomainEntity]);

        $context = $this->createMock(SalesChannelContext::class);

        $salesChannelEntity = $this->createConfiguredMock(SalesChannelEntity::class, [
            'getId' => 'foo',
            'getDomains' => $domains,
        ]);

        $context->expects(static::once())->method('getSalesChannel')
            ->willReturn($salesChannelEntity);

        if ($domainEntityId !== $domainId) {
            static::expectException(SalesChannelDomainNotFoundException::class);
        }

        $expect = $this->contextService->getSalesChannelDomain($domainId, $context);

        static::assertInstanceOf(SalesChannelDomainEntity::class, $expect);
        static::assertSame($expect, $salesChannelDomainEntity);
    }

    public function testGetLocaleOfNullCustomer(): void
    {
        $this->salesChannelContext->expects(static::once())
            ->method('getCustomer')
            ->willReturn(null);

        static::expectException(CheckoutComException::class);

        $this->contextService->getLocaleCode($this->salesChannelContext);
    }

    public function testGetLocaleOfNullLocaleEntity(): void
    {
        $customer = new CustomerEntity();
        $customer->setId('foo');
        $customer->setLanguageId('foo');

        $this->salesChannelContext->expects(static::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $language = $this->createConfiguredMock(LanguageEntity::class, [
            'getId' => 'foo',
            'getLocale' => null,
        ]);
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $language,
        ]);
        $this->languageRepository->entitySearchResults[] = $search;

        static::expectException(CheckoutComException::class);
        $this->contextService->getLocaleCode($this->salesChannelContext);
    }

    public function testGetLocaleOfNullLanguage(): void
    {
        $customer = new CustomerEntity();
        $customer->setId('foo');
        $customer->setLanguageId('foo');

        $this->salesChannelContext->expects(static::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => null,
        ]);
        $this->languageRepository->entitySearchResults[] = $search;

        static::expectException(CheckoutComException::class);
        $this->contextService->getLocaleCode($this->salesChannelContext);
    }

    public function testGetLocale(): void
    {
        $customer = new CustomerEntity();
        $customer->setId('foo');
        $customer->setLanguageId('foo');

        $this->salesChannelContext->expects(static::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $language = $this->createConfiguredMock(LanguageEntity::class, [
            'getId' => 'foo',
            'getLocale' => $this->createConfiguredMock(LocaleEntity::class, [
                'getCode' => 'foo',
            ]),
        ]);
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $language,
        ]);
        $this->languageRepository->entitySearchResults[] = $search;

        $expect = $this->contextService->getLocaleCode($this->salesChannelContext);
        static::assertIsString($expect);
    }

    public function getSalesChannelDomainProvider(): array
    {
        return [
            'Test not found domain' => [
                '123',
            ],
            'Test found domain' => [
                'foo',
            ],
        ];
    }

    public function getLocaleProvider(): array
    {
        return [
            'Test context do not have customer' => [
                false,
                false,
                false,
            ],
            'Test not found language' => [
                true,
                false,
                false,
            ],
            'Test not found locale' => [
                true,
                true,
                false,
            ],
            'Test found locale' => [
                true,
                true,
                true,
            ],
        ];
    }
}
