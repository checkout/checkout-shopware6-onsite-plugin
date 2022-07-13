<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Product;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ProductService
{
    private LoggerInterface $logger;

    private EntityRepositoryInterface $productRepository;

    public function __construct(LoggerInterface $logger, EntityRepositoryInterface $productRepository)
    {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function increaseStock(string $productId, int $quantity, Context $context): void
    {
        $product = $this->getProductById($productId, $context);

        $this->productRepository->update([
            [
                'id' => $productId,
                'stock' => $product->getStock() + $quantity,
            ],
        ], $context);
    }

    private function getProductById(string $productId, Context $context): ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->setLimit(1);

        $product = $this->productRepository->search($criteria, $context)->first();
        if (!$product instanceof ProductEntity) {
            $this->logger->critical(
                sprintf('Could not find an Product with ID: %s.', $productId)
            );

            throw new ProductNotFoundException($productId);
        }

        return $product;
    }
}
