<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Services\Product;

use Cko\Shopware6\Service\Product\ProductService;
use Cko\Shopware6\Tests\Fakes\FakeEntityRepository;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

class ProductServiceTest extends TestCase
{
    use ContextTrait;

    private $salesChannelContext;

    private ProductService $productService;

    private FakeEntityRepository $productRepository;

    protected function setUp(): void
    {
        $this->productRepository = new FakeEntityRepository(new ProductDefinition());
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->productService = new ProductService(
            $this->createMock(LoggerInterface::class),
            $this->productRepository
        );
    }

    public function testIncreaseStockOfNotFoundProduct(): void
    {
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => null,
        ]);

        $this->productRepository->entitySearchResults[] = $search;

        static::expectException(ProductNotFoundException::class);

        $this->productService->increaseStock(
            'foo',
            5,
            $this->salesChannelContext->getContext()
        );
    }

    public function testIncreaseStockSuccess(): void
    {
        $productId = 'foo';

        $mock = $this->createConfiguredMock(ProductEntity::class, [
            'getId' => $productId,
            'getStock' => 3,
        ]);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $mock,
        ]);

        $this->productRepository->entitySearchResults[] = $search;

        $event = $this->createMock(EntityWrittenContainerEvent::class);
        $this->productRepository->entityWrittenContainerEvents[] = $event;

        $this->productService->increaseStock(
            $productId,
            5,
            $this->salesChannelContext->getContext()
        );

        static::assertNotEmpty($this->productRepository->data);
        static::assertSame(8, $this->productRepository->data[0][0]['stock']);
    }
}
