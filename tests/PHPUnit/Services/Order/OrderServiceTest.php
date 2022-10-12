<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Services\Order;

use Cko\Shopware6\Exception\OrderNotFoundException;
use Cko\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use Cko\Shopware6\Service\LoggerService;
use Cko\Shopware6\Service\Order\OrderService;
use Cko\Shopware6\Service\Transition\OrderTransitionService;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Cko\Shopware6\Tests\Fakes\FakeEntityRepository;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Cko\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService as CoreOrderService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderServiceTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    private FakeEntityRepository $orderRepository;

    private FakeEntityRepository $orderAddressRepository;

    private FakeEntityRepository $orderDeliveryRepository;

    /**
     * @var MockObject|OrderTransitionService
     */
    private $orderTransitionService;

    /**
     * @var MockObject|CoreOrderService
     */
    private $coreOrderService;

    private OrderService $orderService;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->orderRepository = new FakeEntityRepository(new OrderDefinition());
        $this->orderAddressRepository = new FakeEntityRepository(new OrderAddressDefinition());
        $this->orderDeliveryRepository = new FakeEntityRepository(new OrderDeliveryDefinition());
        $this->coreOrderService = $this->createMock(CoreOrderService::class);
        $this->orderTransitionService = $this->createMock(OrderTransitionService::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->orderService = new OrderService(
            $this->createMock(LoggerService::class),
            $this->orderRepository,
            $this->orderAddressRepository,
            $this->orderDeliveryRepository,
            $this->coreOrderService,
            $this->orderTransitionService
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->orderService->getDecorated();
    }

    public function testLastOrderId(): void
    {
        $lastOrderId = 'foo';
        $this->orderService->setRequestLastOrderId($lastOrderId);
        static::assertSame($lastOrderId, $this->orderService->getRequestLastOrderId());
    }

    /**
     * @dataProvider getOrderProvider
     */
    public function testGetOrder(?string $orderId, bool $expectFound): void
    {
        if (!$expectFound) {
            static::expectException(OrderNotFoundException::class);
        }

        $mockOrder = $this->createConfiguredMock(OrderEntity::class, [
            'getId' => $orderId,
        ]);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $expectFound ? $mockOrder : null,
        ]);

        $this->orderRepository->entitySearchResults[] = $search;

        $order = $this->orderService->getOrder($this->salesChannelContext->getContext(), $orderId);

        static::assertInstanceOf(OrderEntity::class, $order);
    }

    public function testUpdateOrder(): void
    {
        $updatedData = [
            'id' => 'foo',
        ];
        $context = Context::createDefaultContext();

        $event = $this->createMock(EntityWrittenContainerEvent::class);
        $this->orderRepository->entityWrittenContainerEvents[] = $event;

        $this->orderService->updateOrder($updatedData, $context);

        static::assertNotEmpty($this->orderRepository->data);
    }

    /**
     * @dataProvider updateCheckoutCustomFieldsProvider
     */
    public function testUpdateCheckoutCustomFields(string $checkoutReturnUrl, ?string $transactionReturnUrl, ?string $paymentId): void
    {
        $order = $this->getOrder();

        // Get existing custom fields
        $checkoutOrderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutOrderCustomFields->setCheckoutReturnUrl($checkoutReturnUrl);
        $checkoutOrderCustomFields->setTransactionReturnUrl($transactionReturnUrl);
        $checkoutOrderCustomFields->setCheckoutPaymentId($paymentId);

        $event = $this->createMock(EntityWrittenContainerEvent::class);
        $this->orderRepository->entityWrittenContainerEvents[] = $event;

        $this->orderService->updateCheckoutCustomFields($order, $checkoutOrderCustomFields, $this->salesChannelContext->getContext());

        static::assertNotEmpty($this->orderRepository->data);
        static::assertArrayHasKey('customFields', $this->orderRepository->data[0][0]);
        static::assertArrayHasKey(OrderService::CHECKOUT_CUSTOM_FIELDS, $this->orderRepository->data[0][0]['customFields']);
        static::assertSame($checkoutOrderCustomFields->jsonSerialize(), $this->orderRepository->data[0][0]['customFields'][OrderService::CHECKOUT_CUSTOM_FIELDS]);
    }

    /**
     * @dataProvider processTransitionProvider
     */
    public function testProcessTransition(bool $expectThrowException, string $checkoutPaymentStatus): void
    {
        if ($expectThrowException) {
            static::expectException(Exception::class);
        }

        $skipArray = [
            CheckoutPaymentService::STATUS_REFUNDED,
            CheckoutPaymentService::STATUS_PARTIALLY_REFUNDED,
            CheckoutPaymentService::STATUS_PENDING,
            CheckoutPaymentService::STATUS_CANCELED,
            CheckoutPaymentService::STATUS_EXPIRED,
            CheckoutPaymentService::STATUS_DECLINED,
        ];
        if (\in_array($checkoutPaymentStatus, $skipArray, true)) {
            $this->orderTransitionService->expects(static::never())
                ->method('setTransitionState');
        } else {
            $this->orderTransitionService->expects(static::exactly($expectThrowException ? 0 : 1))
                ->method('setTransitionState');
        }

        $settings = $this->createMock(SettingStruct::class);

        $this->orderService->processTransition($this->getOrder(), $settings, $checkoutPaymentStatus, $this->salesChannelContext->getContext());
    }

    public function getOrderProvider(): array
    {
        return [
            'Test did not find an order' => [
                '123',
                false,
            ],
            'Test found an order' => [
                '12345',
                true,
            ],
        ];
    }

    public function updateCheckoutCustomFieldsProvider(): array
    {
        return [
            'Test null checkout return url' => [
                '',
                'https://www.example.com/return',
                'payment_id',
            ],
            'Test null transaction return url' => [
                'https://www.example.com/return',
                '',
                'payment_id',
            ],
            'Test null payment id' => [
                'https://www.example.com/return',
                'https://www.example.com/return',
                null,
            ],
        ];
    }

    public function processTransitionProvider(): array
    {
        return [
            'Test not found checkout status must throw exception' => [
                true,
                'Do not exists checkout status',
            ],
            'Test transition order success with checkout status is captured' => [
                false,
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test transition order success with checkout status is failed' => [
                false,
                CheckoutPaymentService::STATUS_FAILED,
            ],
            'Test transition order success with checkout status is authorized' => [
                false,
                CheckoutPaymentService::STATUS_AUTHORIZED,
            ],
            'Test transition order success with checkout status is void' => [
                false,
                CheckoutPaymentService::STATUS_VOID,
            ],
            'Test transition order success with checkout status is refunded' => [
                false,
                CheckoutPaymentService::STATUS_REFUNDED,
            ],
            'Test transition order success with checkout status is partial refunded' => [
                false,
                CheckoutPaymentService::STATUS_PARTIALLY_REFUNDED,
            ],
            'Test transition order success with checkout status is pending' => [
                false,
                CheckoutPaymentService::STATUS_PENDING,
            ],
            'Test transition order success with checkout status is canceled' => [
                false,
                CheckoutPaymentService::STATUS_CANCELED,
            ],
            'Test transition order success with checkout status is expired' => [
                false,
                CheckoutPaymentService::STATUS_EXPIRED,
            ],
            'Test transition order success with checkout status is declined' => [
                false,
                CheckoutPaymentService::STATUS_DECLINED,
            ],
        ];
    }

    public function testGetOrderByOrderNumber(): void
    {
        $orderRepository = $this->createMock(EntityRepository::class);
        $order = new OrderEntity();
        $id = Uuid::randomHex();
        $order->setId($id);
        $orderRepository->expects(static::once())->method('search')->willReturn(
            new EntitySearchResult(
                'order',
                1,
                new EntityCollection([$order]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $orderService = new OrderService(
            $this->createMock(LoggerService::class),
            $orderRepository,
            $this->orderAddressRepository,
            $this->orderDeliveryRepository,
            $this->coreOrderService,
            $this->orderTransitionService
        );

        $order = $orderService->getOrderByOrderNumber('test', Context::createDefaultContext());

        static::assertSame($id, $order->getId());
    }

    public function testGetOrderByOrderNumberWithException(): void
    {
        static::expectException(OrderNotFoundException::class);

        $orderRepository = $this->createMock(EntityRepository::class);
        $orderRepository->expects(static::once())->method('search')->willReturn(
            new EntitySearchResult(
                'order',
                1,
                new EntityCollection([]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $orderService = new OrderService(
            $this->createMock(LoggerService::class),
            $orderRepository,
            $this->orderAddressRepository,
            $this->orderDeliveryRepository,
            $this->coreOrderService,
            $this->orderTransitionService
        );

        $orderService->getOrderByOrderNumber('test', Context::createDefaultContext());
    }

    public function testIsOnlyHaveShippingCostsReturnFalse(): void
    {
        $order = new OrderEntity();
        $order->setId('foo');
        $order->setPrice(
            new CartPrice(
                5.5,
                5.5,
                5.5,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                'foo'
            )
        );

        $shippingCostsLineItems = $this->createConfiguredMock(LineItemCollection::class, [
            'getPrices' => $this->createConfiguredMock(PriceCollection::class, [
                'sum' => $this->createConfiguredMock(CalculatedPrice::class, [
                    'getTotalPrice' => 5.5,
                ]),
            ]),
        ]);

        $lineItems = $this->createConfiguredMock(LineItemCollection::class, [
            'getPrices' => $this->createConfiguredMock(PriceCollection::class, [
                'sum' => $this->createConfiguredMock(CalculatedPrice::class, [
                    'getTotalPrice' => 1.0,
                ]),
            ]),
        ]);

        $result = $this->orderService->isOnlyHaveShippingCosts($order, $lineItems, $shippingCostsLineItems);

        static::assertIsBool($result);
        static::assertFalse($result);
    }

    public function testIsOnlyHaveShippingCostsReturnTrue(): void
    {
        $order = new OrderEntity();
        $order->setId('foo');
        $order->setPrice(
            new CartPrice(
                5.5,
                5.5,
                5.5,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                'foo'
            )
        );

        $shippingCostsLineItems = $this->createConfiguredMock(LineItemCollection::class, [
            'getPrices' => $this->createConfiguredMock(PriceCollection::class, [
                'sum' => $this->createConfiguredMock(CalculatedPrice::class, [
                    'getTotalPrice' => 5.5,
                ]),
            ]),
        ]);

        $lineItems = $this->createConfiguredMock(LineItemCollection::class, [
            'getPrices' => $this->createConfiguredMock(PriceCollection::class, [
                'sum' => $this->createConfiguredMock(CalculatedPrice::class, [
                    'getTotalPrice' => 0.0,
                ]),
            ]),
        ]);

        $result = $this->orderService->isOnlyHaveShippingCosts($order, $lineItems, $shippingCostsLineItems);

        static::assertIsBool($result);
        static::assertTrue($result);
    }
}
