<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Services\Order;

use Cko\Shopware6\Exception\CheckoutComException;
use Cko\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use Cko\Shopware6\Factory\SettingsFactory;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use Cko\Shopware6\Service\Extractor\OrderExtractor;
use Cko\Shopware6\Service\LoggerService;
use Cko\Shopware6\Service\Order\OrderCheckoutService;
use Cko\Shopware6\Service\Order\OrderService;
use Cko\Shopware6\Service\Order\OrderTransactionService;
use Cko\Shopware6\Service\PaymentMethodService;
use Cko\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Cko\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;

class OrderCheckoutServiceTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    private OrderCheckoutService $orderCheckoutService;

    /**
     * @var MockObject|OrderCheckoutService
     */
    private $orderService;

    /**
     * @var OrderExtractor|MockObject
     */
    private $orderExtractor;

    /**
     * @var MockObject|OrderTransactionService
     */
    private $orderTransitionService;

    /**
     * @var CheckoutPaymentService|MockObject
     */
    private $checkoutPaymentService;

    /**
     * @var PaymentMethodService|MockObject
     */
    private $paymentMethodService;

    /**
     * @var SettingsFactory|MockObject
     */
    private $settingFactory;

    /**
     * @var MockObject|\Shopware\Core\System\SalesChannel\SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->orderService = $this->createMock(OrderService::class);
        $this->orderExtractor = $this->createMock(OrderExtractor::class);
        $this->orderTransitionService = $this->createMock(OrderTransactionService::class);
        $this->checkoutPaymentService = $this->createMock(CheckoutPaymentService::class);
        $this->paymentMethodService = $this->createMock(PaymentMethodService::class);
        $this->settingFactory = $this->createMock(SettingsFactory::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->orderCheckoutService = new OrderCheckoutService(
            $this->createMock(LoggerService::class),
            $this->orderService,
            $this->orderExtractor,
            $this->orderTransitionService,
            $this->checkoutPaymentService,
            $this->paymentMethodService,
            $this->settingFactory
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->orderCheckoutService->getDecorated();
    }

    public function testGetCheckoutPaymentOfNullCheckoutOrderId(): void
    {
        $order = $this->getOrder();

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        static::expectException(CheckoutPaymentIdNotFoundException::class);

        $this->orderCheckoutService->getCheckoutPayment($order->getId(), $this->salesChannelContext->getContext());
    }

    public function testGetCheckoutPaymentShouldThrowException(): void
    {
        $order = $this->getOrder('foo');

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentDetails')
            ->willThrowException(new CheckoutComException('foo'));

        static::expectException(CheckoutComException::class);

        $this->orderCheckoutService->getCheckoutPayment($order->getId(), $this->salesChannelContext->getContext());
    }

    public function testGetCheckoutPayment(): void
    {
        $order = $this->getOrder('foo');

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $payment = new Payment();

        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentDetails')
            ->willReturn($payment);
        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentActions')
            ->willReturn([]);

        $expect = $this->orderCheckoutService->getCheckoutPayment($order->getId(), $this->salesChannelContext->getContext());

        static::assertInstanceOf(Payment::class, $expect);
    }

    public function testCapturePaymentOfNullCheckoutOrderId(): void
    {
        $order = $this->getOrder();

        static::expectException(CheckoutPaymentIdNotFoundException::class);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderCheckoutService->capturePayment($order->getId(), $this->salesChannelContext->getContext());
    }

    public function testCapturePaymentOfNullPaymentHandler(): void
    {
        $order = $this->getOrder('foo');
        $orderTransaction = $this->getOrderTransaction();
        $orderTransactionCollection = new OrderTransactionCollection();
        $orderTransactionCollection->add($orderTransaction);
        $order->setTransactions($orderTransactionCollection);

        static::expectException(CheckoutComException::class);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction')
            ->willReturn($orderTransaction);

        $this->orderCheckoutService->capturePayment($order->getId(), $this->salesChannelContext->getContext());
    }

    public function testCapturePaymentOfNullPaymentDetail(): void
    {
        $paymentHandler = $this->createMock(PaymentHandler::class);
        $order = $this->getOrder('foo');
        $orderTransaction = $this->getOrderTransaction();
        $orderTransactionCollection = new OrderTransactionCollection();
        $orderTransactionCollection->add($orderTransaction);
        $order->setTransactions($orderTransactionCollection);

        $this->paymentMethodService->expects(static::once())
            ->method('getPaymentHandlerByOrderTransaction')
            ->willReturn($paymentHandler);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction')
            ->willReturn($orderTransaction);

        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentDetails')
            ->willThrowException(new Exception('foo'));

        static::expectException(CheckoutComException::class);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderCheckoutService->capturePayment($order->getId(), $this->salesChannelContext->getContext());
    }

    /**
     * @dataProvider capturePaymentProvider
     */
    public function testCapturePayment(string $paymentStatus): void
    {
        $paymentHandler = $this->createMock(PaymentHandler::class);
        $order = $this->getOrder('foo');
        $orderTransaction = $this->getOrderTransaction();
        $orderTransactionCollection = new OrderTransactionCollection();
        $orderTransactionCollection->add($orderTransaction);
        $order->setTransactions($orderTransactionCollection);

        $this->paymentMethodService->expects(static::once())
            ->method('getPaymentHandlerByOrderTransaction')
            ->willReturn($paymentHandler);

        $payment = $this->createConfiguredMock(Payment::class, [
            'getStatus' => $paymentStatus,
        ]);

        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentDetails')
            ->willReturn($payment);

        $isAuthorized = $paymentStatus === CheckoutPaymentService::STATUS_AUTHORIZED;

        $paymentHandler->expects(static::exactly($isAuthorized ? 1 : 0))
            ->method('capturePayment');

        $this->settingFactory->expects(static::exactly($isAuthorized ? 1 : 0))
            ->method('getSettings');

        $this->orderService->expects(static::exactly($isAuthorized ? 1 : 0))
            ->method('processTransition');

        $this->orderTransitionService->expects(static::exactly($isAuthorized ? 1 : 0))
            ->method('processTransition');

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderCheckoutService->capturePayment($order->getId(), $this->salesChannelContext->getContext());
    }

    public function testVoidPaymentOfNullCheckoutOrderId(): void
    {
        $order = $this->getOrder();

        static::expectException(CheckoutPaymentIdNotFoundException::class);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderCheckoutService->voidPayment($order->getId(), $this->salesChannelContext->getContext());
    }

    public function testVoidPaymentOfNullPaymentHandler(): void
    {
        $order = $this->getOrder('foo');
        $orderTransaction = $this->getOrderTransaction();
        $orderTransactionCollection = new OrderTransactionCollection();
        $orderTransactionCollection->add($orderTransaction);
        $order->setTransactions($orderTransactionCollection);

        static::expectException(CheckoutComException::class);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction')
            ->willReturn($orderTransaction);

        $this->orderCheckoutService->voidPayment($order->getId(), $this->salesChannelContext->getContext());
    }

    public function testVoidPaymentOfNullPaymentDetail(): void
    {
        $paymentHandler = $this->createMock(PaymentHandler::class);
        $order = $this->getOrder('foo');
        $orderTransaction = $this->getOrderTransaction();
        $orderTransactionCollection = new OrderTransactionCollection();
        $orderTransactionCollection->add($orderTransaction);
        $order->setTransactions($orderTransactionCollection);

        $this->paymentMethodService->expects(static::once())
            ->method('getPaymentHandlerByOrderTransaction')
            ->willReturn($paymentHandler);

        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentDetails')
            ->willThrowException(new Exception('foo'));

        static::expectException(CheckoutComException::class);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction')
            ->willReturn($orderTransaction);

        $this->orderCheckoutService->voidPayment($order->getId(), $this->salesChannelContext->getContext());
    }

    /**
     * @dataProvider voidPaymentProvider
     */
    public function testVoidPayment(string $paymentStatus): void
    {
        $paymentHandler = $this->createMock(PaymentHandler::class);
        $order = $this->getOrder('foo');
        $orderTransaction = $this->getOrderTransaction();
        $orderTransactionCollection = new OrderTransactionCollection();
        $orderTransactionCollection->add($orderTransaction);
        $order->setTransactions($orderTransactionCollection);

        $this->paymentMethodService->expects(static::once())
            ->method('getPaymentHandlerByOrderTransaction')
            ->willReturn($paymentHandler);

        $payment = $this->createConfiguredMock(Payment::class, [
            'getStatus' => $paymentStatus,
        ]);

        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentDetails')
            ->willReturn($payment);

        $isAuthorized = $paymentStatus === CheckoutPaymentService::STATUS_AUTHORIZED;

        $paymentHandler->expects(static::exactly($isAuthorized ? 1 : 0))
            ->method('voidPayment');

        $this->settingFactory->expects(static::exactly($isAuthorized ? 1 : 0))
            ->method('getSettings');

        $this->orderService->expects(static::exactly($isAuthorized ? 1 : 0))
            ->method('processTransition');

        $this->orderTransitionService->expects(static::exactly($isAuthorized ? 1 : 0))
            ->method('processTransition');

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderCheckoutService->voidPayment($order->getId(), $this->salesChannelContext->getContext());
    }

    public function capturePaymentProvider(): array
    {
        return [
            'Test payment status is not authorized' => [
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test capture successfully' => [
                CheckoutPaymentService::STATUS_AUTHORIZED,
            ],
        ];
    }

    public function voidPaymentProvider(): array
    {
        return [
            'Test payment status is not authorized' => [
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test void successfully' => [
                CheckoutPaymentService::STATUS_AUTHORIZED,
            ],
        ];
    }
}
