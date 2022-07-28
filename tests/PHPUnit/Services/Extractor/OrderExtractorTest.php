<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Extractor;

use CheckoutCom\Shopware6\Service\AddressService;
use CheckoutCom\Shopware6\Service\Extractor\OrderExtractor;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderExtractorTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    /**
     * @var MockObject|AddressService
     */
    private $addresService;

    private OrderExtractor $orderExtractor;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->addresService = $this->createMock(AddressService::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->orderExtractor = new OrderExtractor(
            $this->createMock(LoggerService::class),
            $this->addresService
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->orderExtractor->getDecorated();
    }

    /**
     * @dataProvider extractOrderNumberProvider
     */
    public function testExtractOrderNumber(?string $orderNumber): void
    {
        $order = new OrderEntity();
        $order->setId('foo');

        if ($orderNumber === null) {
            static::expectException(Exception::class);
        } else {
            $order->setOrderNumber($orderNumber);
        }

        $actual = $this->orderExtractor->extractOrderNumber($order);
        static::assertSame($orderNumber, $actual);
    }

    /**
     * @dataProvider extractCustomerProvider
     */
    public function testExtractCustomer(bool $hasOrderCustomer): void
    {
        $order = $this->getOrder();
        if ($hasOrderCustomer) {
            $orderCustomer = $this->createMock(OrderCustomerEntity::class);
            $order->setOrderCustomer($orderCustomer);
        } else {
            static::expectException(EntityNotFoundException::class);
        }

        $customer = $this->orderExtractor->extractCustomer($order);
        static::assertInstanceOf(OrderCustomerEntity::class, $customer);
    }

    /**
     * @dataProvider extractBillingAddressProvider
     */
    public function testExtractBillingAddress(bool $hasBillingAddress): void
    {
        $order = new OrderEntity();
        $order->setId('foo');

        if ($hasBillingAddress) {
            $billingAddress = $this->createConfiguredMock(OrderAddressEntity::class, [
                'getId' => 'foo',
            ]);
            $order->setBillingAddress($billingAddress);
            $this->addresService->expects(static::once())
                ->method('getOrderAddress')
                ->willReturn($billingAddress);
        } else {
            static::expectException(Exception::class);
        }

        $this->orderExtractor->extractBillingAddress($order, $this->salesChannelContext);
    }

    /**
     * @dataProvider extractShippingAddressProvider
     */
    public function testExtractShippingAddress(bool $hasOrderCollection, bool $hasOrderDelivery, bool $hasShippingOrderAddress): void
    {
        $order = new OrderEntity();
        $order->setId('foo');

        $shippingAddress = null;
        $orderDelivery = null;

        if ($hasShippingOrderAddress) {
            $shippingAddress = $this->createConfiguredMock(OrderAddressEntity::class, [
                'getId' => 'foo',
            ]);
        } else {
            static::expectException(Exception::class);
        }

        if ($hasOrderDelivery) {
            $orderDelivery = $this->createConfiguredMock(OrderDeliveryEntity::class, [
                'getShippingOrderAddress' => $shippingAddress,
            ]);
        } else {
            static::expectException(Exception::class);
        }

        if ($hasOrderCollection) {
            $deliveries = $this->createConfiguredMock(OrderDeliveryCollection::class, [
                'first' => $orderDelivery,
            ]);

            $order->setDeliveries($deliveries);
        } else {
            static::expectException(Exception::class);
        }

        if ($hasOrderCollection && $hasOrderDelivery && $hasShippingOrderAddress) {
            $this->addresService->expects(static::once())
                ->method('getOrderAddress')
                ->willReturn($shippingAddress);
        }

        $this->orderExtractor->extractShippingAddress($order, $this->salesChannelContext);
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

    public function testExtractOrderDeliveryOfNullOrderDeliveryCollection(): void
    {
        $order = $this->createMock(OrderEntity::class);

        static::expectException(Exception::class);
        $this->orderExtractor->extractOrderDelivery($order);
    }

    public function testExtractOrderDeliveryOfNullOrderDeliveryEntity(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getDeliveries' => new OrderDeliveryCollection(),
        ]);

        static::expectException(Exception::class);
        $this->orderExtractor->extractOrderDelivery($order);
    }

    public function testExtractOrderDeliverySuccessful(): void
    {
        $orderDelivery = new OrderDeliveryEntity();
        $orderDelivery->setId('foo');

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getDeliveries' => new OrderDeliveryCollection([$orderDelivery]),
        ]);

        $result = $this->orderExtractor->extractOrderDelivery($order);
        static::assertInstanceOf(OrderDeliveryEntity::class, $result);
    }

    public function testExtractOrderTransactionOfNullOrderTransactionCollection(): void
    {
        $order = $this->createMock(OrderEntity::class);

        static::expectException(Exception::class);
        $this->orderExtractor->extractLatestOrderTransaction($order);
    }

    public function testExtractOrderTransactionOfNullOrderDeliveryEntity(): void
    {
        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getTransactions' => new OrderTransactionCollection(),
        ]);

        static::expectException(Exception::class);
        $this->orderExtractor->extractLatestOrderTransaction($order);
    }

    public function testExtractOrderTransactionSuccessful(): void
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('foo');

        $order = $this->createConfiguredMock(OrderEntity::class, [
            'getTransactions' => new OrderTransactionCollection([$orderTransaction]),
        ]);

        $result = $this->orderExtractor->extractLatestOrderTransaction($order);
        static::assertInstanceOf(OrderTransactionEntity::class, $result);
    }

    public function extractOrderNumberProvider(): array
    {
        return [
            'Test could not find order number' => [
                null,
            ],
            'Test found order number' => [
                '1234',
            ],
        ];
    }

    public function extractCustomerProvider(): array
    {
        return [
            'Test could not find order customer' => [
                false,
            ],
            'Test found order customer' => [
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
            'Test found order currency' => [
                true,
            ],
        ];
    }

    public function extractBillingAddressProvider(): array
    {
        return [
            'Test could not find order billing address' => [
                false,
            ],
            'Test found order billing address' => [
                true,
            ],
        ];
    }

    public function extractShippingAddressProvider(): array
    {
        return [
            'Test could not find order collection' => [
                false,
                false,
                false,
            ],
            'Test could not find order delivery' => [
                true,
                false,
                false,
            ],
            'Test could not find order shipping address' => [
                true,
                true,
                false,
            ],
            'Test found order shipping address' => [
                true,
                true,
                true,
            ],
        ];
    }
}
