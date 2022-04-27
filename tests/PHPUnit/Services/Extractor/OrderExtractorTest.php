<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Extractor;

use CheckoutCom\Shopware6\Service\CustomerService;
use CheckoutCom\Shopware6\Service\Extractor\OrderExtractor;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderExtractorTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    /**
     * @var MockObject|CustomerService
     */
    private $customerService;

    private OrderExtractor $orderExtractor;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->customerService = $this->createMock(CustomerService::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->orderExtractor = new OrderExtractor(
            $this->createMock(LoggerService::class),
            $this->customerService
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->orderExtractor->getDecorated();
    }

    /**
     * @dataProvider extractCustomerProvider
     */
    public function testExtractCustomer(bool $hasOrderCustomer, bool $hasCustomerId): void
    {
        $order = $this->getOrder();
        if ($hasOrderCustomer) {
            $orderCustomer = $this->createConfiguredMock(OrderCustomerEntity::class, [
                'getCustomerId' => $hasCustomerId ? 'foo' : null,
            ]);
            $order->setOrderCustomer($orderCustomer);

            if (!$hasCustomerId) {
                static::expectException(EntityNotFoundException::class);
            }
        } else {
            static::expectException(EntityNotFoundException::class);
        }

        $this->customerService->expects(static::exactly($hasOrderCustomer && $hasCustomerId ? 1 : 0))
            ->method('getCustomer');

        $this->orderExtractor->extractCustomer($order, $this->salesChannelContext);
    }

    /**
     * @dataProvider extractCurrencyProvider
     */
    public function testExtractCurrency(bool $hasCurrency): void
    {
        $currency = $this->createMock(CurrencyEntity::class);
        $order = $this->getOrder();
        if ($hasCurrency) {
            $order->setCurrency($currency);
        } else {
            static::expectException(EntityNotFoundException::class);
        }

        $actualCurrency = $this->orderExtractor->extractCurrency($order);

        static::assertSame($currency, $actualCurrency);
    }

    public function extractCustomerProvider(): array
    {
        return [
            'Test could not find order customer' => [
                false,
                true,
            ],
            'Test could not found customer ID from Order Customer Entity' => [
                true,
                false,
            ],
            'Test found order order customer' => [
                true,
                true,
            ],
        ];
    }

    public function extractCurrencyProvider(): array
    {
        return [
            'Test could not find order currency' => [
                false,
            ],
            'Test found order order currency' => [
                true,
            ],
        ];
    }
}
