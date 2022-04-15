<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Facade;

use CheckoutCom\Shopware6\Facade\PaymentPayFacade;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Extractor\OrderExtractor;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use CheckoutCom\Shopware6\Struct\PaymentHandler\HandlerPrepareProcessStruct;
use CheckoutCom\Shopware6\Struct\SettingStruct;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentPayFacadeTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    /**
     * @var SettingsFactory|MockObject
     */
    protected $settingsFactory;

    /**
     * @var CheckoutPaymentService|MockObject
     */
    protected $checkoutPaymentService;

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

    protected PaymentPayFacade $paymentPayFacade;

    public function setUp(): void
    {
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->settingsFactory = $this->createMock(SettingsFactory::class);
        $this->checkoutPaymentService = $this->createMock(CheckoutPaymentService::class);
        $this->orderExtractor = $this->createMock(OrderExtractor::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->orderTransactionService = $this->createMock(OrderTransactionService::class);

        $this->paymentPayFacade = new PaymentPayFacade(
            $this->createMock(LoggerService::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->settingsFactory,
            $this->checkoutPaymentService,
            $this->orderExtractor,
            $this->orderService,
            $this->orderTransactionService,
        );
    }

    /**
     * @dataProvider payProvider
     */
    public function testPay(?string $checkoutPaymentId, bool $approved, bool $freeTax, ?string $checkoutPaymentStatus = null): void
    {
        $order = $this->setUpOrder($checkoutPaymentId, $freeTax);
        $orderTransaction = $this->getOrderTransaction();
        $orderTransaction->setOrder($order);

        $payment = new Payment();
        $payment->assign([
            'id' => 'foo',
            'approved' => $approved,
            'status' => $checkoutPaymentStatus,
        ]);

        $paymentHandler = $this->createMock(PaymentHandler::class);
        $settings = new SettingStruct();

        $this->settingsFactory->expects(static::once())
            ->method('getSettings')
            ->willReturn($settings);

        if (empty($checkoutPaymentId)) {
            $currency = $this->getCurrency();
            $customer = $this->setUpCustomer();

            $this->orderExtractor->expects(static::once())
                ->method('extractCustomer')
                ->willReturn($customer);

            $this->orderExtractor->expects(static::once())
                ->method('extractCurrency')
                ->willReturn($currency);

            $this->checkoutPaymentService->expects(static::once())
                ->method('requestPayment')
                ->willReturn($payment);
        } else {
            $this->checkoutPaymentService->expects(static::once())
                ->method('getPaymentDetails')
                ->willReturn($payment);

            if ($checkoutPaymentStatus === CheckoutPaymentService::STATUS_DECLINED) {
                $currency = $this->getCurrency();
                $customer = $this->setUpCustomer();

                $this->orderExtractor->expects(static::once())
                    ->method('extractCustomer')
                    ->willReturn($customer);

                $this->orderExtractor->expects(static::once())
                    ->method('extractCurrency')
                    ->willReturn($currency);

                $this->checkoutPaymentService->expects(static::once())
                    ->method('requestPayment')
                    ->willReturn($payment);
            }
        }

        $this->orderService->expects(static::exactly($approved ? 1 : 0))
            ->method('updateCheckoutCustomFields');

        $this->orderTransactionService->expects(static::exactly($approved ? 1 : 0))
            ->method('processTransition');

        $this->orderService->expects(static::once())
            ->method('processTransition')
            ->withConsecutive(
                [
                    static::equalTo($order),
                    static::equalTo($settings),
                    $approved ? $checkoutPaymentStatus : CheckoutPaymentService::STATUS_FAILED,
                    $this->salesChannelContext->getContext(),
                ],
            );

        if (!$approved) {
            static::expectException(AsyncPaymentProcessException::class);
        }

        $transaction = new AsyncPaymentTransactionStruct($orderTransaction, $order, 'foo url');
        $payProcess = $this->paymentPayFacade->pay($paymentHandler, $transaction, $this->createMock(RequestDataBag::class), $this->salesChannelContext);

        static::assertInstanceOf(HandlerPrepareProcessStruct::class, $payProcess);
    }

    public function payProvider(): array
    {
        return [
            'Test empty checkout payment id and request create payment but it has not been approved' => [
                null,
                false,
                true,
            ],
            'Test empty checkout payment id and request create payment and it has been approved with free tax' => [
                null,
                true,
                true,
                'expect any checkout status',
            ],
            'Test empty checkout payment id and request create payment and it has been approved without free tax' => [
                null,
                true,
                false,
                'expect any checkout status',
            ],
            'Test has checkout payment id and request get payment detail but it has not been approved' => [
                'checkout id',
                false,
                true,
            ],
            'Test has checkout payment id and request get payment detail and it has been approved with free tax' => [
                'checkout id',
                true,
                true,
                'expect any checkout status',
            ],
            'Test has checkout payment id and request get payment detail and it has been approved without free tax' => [
                'checkout id',
                true,
                false,
                'expect any checkout status',
            ],
            'Test has checkout payment id, request get payment detail and payment is not approved, status is declined' => [
                'checkout id',
                false,
                false,
                CheckoutPaymentService::STATUS_DECLINED,
            ],
        ];
    }

    private function setUpCustomer(): CustomerEntity
    {
        $firstName = 'Foo';
        $lastName = 'Bar';
        $email = 'email@email.com';
        $street = 'Street';
        $city = 'City';
        $zip = 'Zip';
        $customer = $this->getCustomerEntity($firstName, $lastName, $email);
        $customerAddress = $this->getCustomerAddressEntity($firstName, $lastName, $street, $city, $zip);

        $customer->setActiveShippingAddress($customerAddress);

        return $customer;
    }

    private function setUpOrder(?string $checkoutPaymentId, bool $freeTax): OrderEntity
    {
        $order = $this->getOrder($checkoutPaymentId);
        $order->setTaxStatus($freeTax ? CartPrice::TAX_STATE_FREE : CartPrice::TAX_STATE_NET);

        return $order;
    }
}
