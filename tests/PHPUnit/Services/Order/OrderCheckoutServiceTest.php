<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Order;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\Order\OrderCheckoutService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use CheckoutCom\Shopware6\Service\PaymentMethodService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
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
        $this->orderTransitionService = $this->createMock(OrderTransactionService::class);
        $this->checkoutPaymentService = $this->createMock(CheckoutPaymentService::class);
        $this->paymentMethodService = $this->createMock(PaymentMethodService::class);
        $this->settingFactory = $this->createMock(SettingsFactory::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->orderCheckoutService = new OrderCheckoutService(
            $this->createMock(LoggerService::class),
            $this->orderService,
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

    /**
     * @dataProvider getCheckoutPaymentProvider
     */
    public function testGetCheckoutPayment(bool $hasCheckoutOrderId, bool $throwException): void
    {
        $order = $this->getOrder($hasCheckoutOrderId ? 'foo' : null);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        if (!$hasCheckoutOrderId) {
            static::expectException(CheckoutPaymentIdNotFoundException::class);
        } else {
            if ($throwException) {
                $this->checkoutPaymentService->expects(static::once())
                    ->method('getPaymentDetails')
                    ->willThrowException(new CheckoutComException('foo'));

                static::expectException(CheckoutComException::class);
            } else {
                $payment = new Payment();

                $this->checkoutPaymentService->expects(static::once())
                    ->method('getPaymentDetails')
                    ->willReturn($payment);
                $this->checkoutPaymentService->expects(static::once())
                    ->method('getPaymentActions')
                    ->willReturn([]);
            }
        }

        $expect = $this->orderCheckoutService->getCheckoutPayment($order->getId(), $this->salesChannelContext->getContext());

        static::assertInstanceOf(Payment::class, $expect);
    }

    /**
     * @dataProvider capturePaymentProvider
     */
    public function testCapturePayment(
        bool $hasCheckoutOrderId,
        bool $hasOrderTransactionCollection,
        bool $hasOrderTransactionEntity,
        bool $hasPaymentHandler,
        bool $hasPaymentDetail,
        string $paymentStatus
    ): void {
        $paymentHandler = $this->createMock(PaymentHandler::class);
        $order = $this->getOrder($hasCheckoutOrderId ? 'foo' : null);

        if (!$hasCheckoutOrderId) {
            static::expectException(CheckoutPaymentIdNotFoundException::class);
        } else {
            if (!$hasOrderTransactionCollection) {
                static::expectException(InvalidOrderException::class);
            } else {
                $orderTransaction = $this->getOrderTransaction();
                $orderTransactionCollection = new OrderTransactionCollection();

                if (!$hasOrderTransactionEntity) {
                    static::expectException(InvalidOrderException::class);
                } else {
                    $orderTransactionCollection->add($orderTransaction);

                    if (!$hasPaymentHandler) {
                        static::expectException(CheckoutComException::class);
                    } else {
                        $this->paymentMethodService->expects(static::once())
                            ->method('getPaymentHandlerByOrderTransaction')
                            ->willReturn($paymentHandler);

                        if (!$hasPaymentDetail) {
                            $this->checkoutPaymentService->expects(static::once())
                                ->method('getPaymentDetails')
                                ->willThrowException(new Exception('foo'));

                            static::expectException(CheckoutComException::class);
                        } else {
                            $payment = $this->createConfiguredMock(Payment::class, [
                                'getStatus' => $paymentStatus,
                            ]);

                            $this->checkoutPaymentService->expects(static::once())
                                ->method('getPaymentDetails')
                                ->willReturn($payment);

                            $isAuthorized = $paymentStatus === CheckoutPaymentService::STATUS_AUTHORIZED;

                            $this->settingFactory->expects(static::exactly($isAuthorized ? 1 : 0))
                                ->method('getSettings');

                            $this->orderService->expects(static::exactly($isAuthorized ? 1 : 0))
                                ->method('processTransition');

                            $this->orderTransitionService->expects(static::exactly($isAuthorized ? 1 : 0))
                                ->method('processTransition');
                        }
                    }
                }

                $order->setTransactions($orderTransactionCollection);
            }
        }

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderCheckoutService->capturePayment($order->getId(), $this->salesChannelContext->getContext());
    }

    public function getCheckoutPaymentProvider(): array
    {
        return [
            'Test not found checkout order ID' => [
                false,
                true,
            ],
            'Test call api Failed' => [
                true,
                true,
            ],
            'Test call api success' => [
                true,
                false,
            ],
        ];
    }

    public function capturePaymentProvider(): array
    {
        return [
            'Test not found checkout order ID' => [
                false,
                false,
                false,
                false,
                false,
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test not found order transaction collection' => [
                true,
                false,
                false,
                false,
                false,
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test not found order transaction entity' => [
                true,
                true,
                false,
                false,
                false,
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test not found payment handler' => [
                true,
                true,
                true,
                false,
                false,
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test call api fail not found payment' => [
                true,
                true,
                true,
                true,
                false,
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test payment status is not authorized' => [
                true,
                true,
                true,
                true,
                true,
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test capture successfully' => [
                true,
                true,
                true,
                true,
                true,
                CheckoutPaymentService::STATUS_AUTHORIZED,
            ],
        ];
    }
}
