<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Services;

use Cko\Shopware6\Service\AddressService;
use Cko\Shopware6\Tests\Fakes\FakeEntityRepository;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AddressServiceTest extends TestCase
{
    use ContextTrait;

    private FakeEntityRepository $orderAddressRepository;

    private AddressService $addressService;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->orderAddressRepository = new FakeEntityRepository(new OrderAddressDefinition());
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->addressService = new AddressService(
            $this->orderAddressRepository,
        );
    }

    /**
     * @dataProvider getOrderAddressProvider
     */
    public function testGetOrderAddress(?string $addressId, bool $expectFound): void
    {
        if (!$expectFound) {
            static::expectException(AddressNotFoundException::class);
        }

        $mock = $this->createMock(OrderAddressEntity::class);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $expectFound ? $mock : null,
        ]);

        $this->orderAddressRepository->entitySearchResults[] = $search;

        $orderAddress = $this->addressService->getOrderAddress($addressId, $this->salesChannelContext->getContext());

        static::assertInstanceOf(OrderAddressEntity::class, $orderAddress);
    }

    public function getOrderAddressProvider(): array
    {
        return [
            'Test did not find an order address' => [
                '123',
                false,
            ],
            'Test found an order address' => [
                '12345',
                true,
            ],
        ];
    }
}
