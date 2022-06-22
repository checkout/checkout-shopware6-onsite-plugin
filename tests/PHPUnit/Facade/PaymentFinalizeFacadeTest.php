<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Facade;

use CheckoutCom\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use CheckoutCom\Shopware6\Facade\PaymentFinalizeFacade;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\CustomerService;
use CheckoutCom\Shopware6\Service\Extractor\OrderExtractor;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentFinalizeFacadeTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    /**
     * @var CheckoutPaymentService|MockObject
     */
    protected $checkoutPaymentService;

    /**
     * @var SettingsFactory|MockObject
     */
    protected $settingsFactory;

    /**
     * @var CustomerService|MockObject
     */
    protected $customerService;

    /**
     * @var OrderExtractor|MockObject
     */
    protected $orderExtractor;

    /**
     * @var OrderService|MockObject
     */
    protected $orderService;

    /**
     * @var OrderTransactionService|MockObject
     */
    protected $orderTransactionService;

    /**
     * @var MockObject|SalesChannelContext
     */
    protected $salesChannelContext;

    protected PaymentFinalizeFacade $paymentFinalizeFacade;

    public function setUp(): void
    {
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->checkoutPaymentService = $this->createMock(CheckoutPaymentService::class);
        $this->settingsFactory = $this->createMock(SettingsFactory::class);
        $this->customerService = $this->createMock(CustomerService::class);
        $this->orderExtractor = $this->createMock(OrderExtractor::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->orderTransactionService = $this->createMock(OrderTransactionService::class);

        $this->paymentFinalizeFacade = new PaymentFinalizeFacade(
            $this->createMock(LoggerService::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->checkoutPaymentService,
            $this->settingsFactory,
            $this->customerService,
            $this->orderExtractor,
            $this->orderService,
            $this->orderTransactionService
        );
    }

    /**
     * @dataProvider finalizeProvider
     */
    public function testFinalize(?string $checkoutPaymentId, ?string $checkoutPaymentStatus = null): void
    {
        $orderCustomer = $this->getOrderCustomerEntity('foo', 'bar', 'baz@example.com');
        $orderCustomer->setCustomerId('foo');
        $order = $this->getOrder($checkoutPaymentId);
        $orderTransaction = $this->getOrderTransaction();
        $orderTransaction->setOrder($order);
        $order->setOrderCustomer($orderCustomer);
        $transaction = new AsyncPaymentTransactionStruct($orderTransaction, $order, 'foo url');

        $paymentHandler = $this->createConfiguredMock(
            PaymentHandler::class,
            [
                'captureWhenFinalize' => true,
            ]
        );

        $payment = new Payment();
        $payment->assign([
            'status' => $checkoutPaymentStatus,
        ]);

        $isRunProcessTransition = 1;

        $this->orderExtractor->expects(static::once())
            ->method('extractCustomer')
            ->willReturn($orderCustomer);

        if (empty($checkoutPaymentId)) {
            $isRunProcessTransition = 0;
            static::expectException(CheckoutPaymentIdNotFoundException::class);
        } elseif ($checkoutPaymentStatus === CheckoutPaymentService::STATUS_DECLINED) {
            $isRunProcessTransition = 0;
            $this->checkoutPaymentService
                ->expects(static::once())
                ->method('getPaymentDetails')
                ->willReturn($payment);

            static::expectException(AsyncPaymentFinalizeException::class);
        } elseif ($checkoutPaymentStatus === CheckoutPaymentService::STATUS_CANCELED) {
            $isRunProcessTransition = 0;
            $this->checkoutPaymentService
                ->expects(static::once())
                ->method('getPaymentDetails')
                ->willReturn($payment);

            static::expectException(CustomerCanceledAsyncPaymentException::class);
        } else {
            $this->checkoutPaymentService
                ->expects(static::once())
                ->method('getPaymentDetails')
                ->willReturn($payment);

            $paymentHandler->expects(static::exactly($checkoutPaymentStatus === CheckoutPaymentService::STATUS_AUTHORIZED ? 1 : 0))
                ->method('capturePayment');
        }

        $this->orderTransactionService
            ->expects(static::exactly($isRunProcessTransition))
            ->method('processTransition');

        $this->orderService
            ->expects(static::exactly($isRunProcessTransition))
            ->method('processTransition');

        $this->paymentFinalizeFacade->finalize($paymentHandler, $transaction, $this->salesChannelContext);
    }

    public function finalizeProvider(): array
    {
        return [
            'Test empty checkout payment id must throw exception' => [
                null,
            ],
            'Test has checkout payment id but must capture checkout payment' => [
                'checkout payment id',
                CheckoutPaymentService::STATUS_AUTHORIZED,
            ],
            'Test has checkout payment id but it does not need to capture checkout payment' => [
                'checkout payment id',
                CheckoutPaymentService::STATUS_CAPTURED,
            ],
            'Test has checkout payment id but must cancel checkout payment' => [
                'checkout payment id',
                CheckoutPaymentService::STATUS_CANCELED,
            ],
            'Test has checkout payment id but must decline checkout payment' => [
                'checkout payment id',
                CheckoutPaymentService::STATUS_DECLINED,
            ],
        ];
    }
}
