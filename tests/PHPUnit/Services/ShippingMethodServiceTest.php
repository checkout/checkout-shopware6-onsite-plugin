<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Service\ShippingMethodService;
use CheckoutCom\Shopware6\Tests\Fakes\FakeEntityRepository;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ShippingMethodServiceTest extends TestCase
{
    use ContextTrait;

    private FakeEntityRepository $shippingMethodRepository;

    private ShippingMethodService $shippingMethodService;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->shippingMethodRepository = new FakeEntityRepository(new ShippingMethodDefinition());
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->shippingMethodService = new ShippingMethodService(
            $this->shippingMethodRepository,
        );
    }

    public function testGetActiveShippingMethods(): void
    {
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getEntities' => new ShippingMethodCollection(),
        ]);

        $this->shippingMethodRepository->entitySearchResults[] = $search;

        $shippingMethodCollection = $this->shippingMethodService->getActiveShippingMethods($this->salesChannelContext);

        static::assertInstanceOf(ShippingMethodCollection::class, $shippingMethodCollection);
    }
}
