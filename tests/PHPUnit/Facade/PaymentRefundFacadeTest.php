<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Facade;

use Cko\Shopware6\Exception\CheckoutComException;
use Cko\Shopware6\Exception\CheckoutPaymentIdNotFoundException;
use Cko\Shopware6\Facade\PaymentRefundFacade;
use Cko\Shopware6\Factory\SettingsFactory;
use Cko\Shopware6\Handler\PaymentHandler;
use Cko\Shopware6\Service\Builder\RefundBuilder;
use Cko\Shopware6\Service\Cart\AbstractCartService;
use Cko\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use Cko\Shopware6\Service\Extractor\AbstractOrderExtractor;
use Cko\Shopware6\Service\Order\AbstractOrderService;
use Cko\Shopware6\Service\Order\AbstractOrderTransactionService;
use Cko\Shopware6\Service\Order\OrderService;
use Cko\Shopware6\Service\PaymentMethodService;
use Cko\Shopware6\Service\Product\ProductService;
use Cko\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Cko\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use Cko\Shopware6\Struct\LineItem\LineItemPayload;
use Cko\Shopware6\Struct\Request\Refund\OrderRefundRequest;
use Cko\Shopware6\Struct\Request\Refund\RefundItemRequestCollection;
use Cko\Shopware6\Struct\SystemConfig\SettingStruct;
use Cko\Shopware6\Struct\WebhookReceiveDataStruct;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentRefundFacadeTest extends TestCase
{
    use ContextTrait;

    private PaymentRefundFacade $paymentRefundFacade;

    /**
     * @var MockObject|OrderConverter
     */
    private $orderConverter;

    /**
     * @var AbstractOrderExtractor|MockObject
     */
    private $orderExtractor;

    /**
     * @var RefundBuilder|MockObject
     */
    private $refundBuilder;

    /**
     * @var ProductService|MockObject
     */
    private $productService;

    /**
     * @var AbstractCartService|MockObject
     */
    private $cartService;

    /**
     * @var AbstractOrderService|MockObject
     */
    private $orderService;

    /**
     * @var AbstractOrderTransactionService|MockObject
     */
    private $orderTransactionService;

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
    private $settingsFactory;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->orderConverter = $this->createMock(OrderConverter::class);
        $this->orderExtractor = $this->createMock(AbstractOrderExtractor::class);
        $this->refundBuilder = $this->createMock(RefundBuilder::class);
        $this->productService = $this->createMock(ProductService::class);
        $this->cartService = $this->createMock(AbstractCartService::class);
        $this->orderService = $this->createMock(AbstractOrderService::class);
        $this->orderTransactionService = $this->createMock(AbstractOrderTransactionService::class);
        $this->checkoutPaymentService = $this->createMock(CheckoutPaymentService::class);
        $this->paymentMethodService = $this->createMock(PaymentMethodService::class);
        $this->settingsFactory = $this->createMock(SettingsFactory::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->paymentRefundFacade = new PaymentRefundFacade(
            $this->createMock(LoggerInterface::class),
            $this->orderConverter,
            $this->orderExtractor,
            $this->refundBuilder,
            $this->productService,
            $this->cartService,
            $this->orderService,
            $this->orderTransactionService,
            $this->checkoutPaymentService,
            $this->paymentMethodService,
            $this->settingsFactory
        );
    }

    public function testRefundPaymentOfEmptyOrderIdRequest(): void
    {
        $orderRefundRequest = new OrderRefundRequest();

        static::expectException(CheckoutComException::class);

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentOfEmptyItemRequest(): void
    {
        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId('foo');

        static::expectException(CheckoutComException::class);

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentOfEmptyBuildLineItems(): void
    {
        $orderId = 'foo';

        $orderCurrency = new CurrencyEntity();
        $orderCurrency->setId('foo');
        $orderCurrency->setIsoCode('foo');

        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setSalesChannelId('foo');

        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId($orderId);
        $orderRefundRequest->setItems(new RefundItemRequestCollection());

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderExtractor->expects(static::once())
            ->method('extractCurrency')
            ->willReturn($orderCurrency);

        $this->refundBuilder->expects(static::once())
            ->method('buildLineItems')
            ->willReturn(new LineItemCollection());

        static::expectException(CheckoutComException::class);

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentOfEmptyCheckoutPaymentId(): void
    {
        $orderId = 'foo';

        $orderCurrency = new CurrencyEntity();
        $orderCurrency->setId('foo');
        $orderCurrency->setIsoCode('foo');

        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setSalesChannelId('foo');
        $order->setLineItems(new OrderLineItemCollection());

        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId($orderId);
        $orderRefundRequest->setItems(new RefundItemRequestCollection());

        $lineItems = new LineItemCollection([new LineItem('foo', 'bar')]);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderExtractor->expects(static::once())
            ->method('extractCurrency')
            ->willReturn($orderCurrency);

        $this->refundBuilder->expects(static::once())
            ->method('buildLineItems')
            ->willReturn($lineItems);

        $this->settingsFactory->expects(static::once())
            ->method('getSettings');

        static::expectException(CheckoutPaymentIdNotFoundException::class);

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundedPaymentFromHubThrowsError(): void
    {
        $orderId = 'foo';

        $checkoutOrderCustomFields = new OrderCustomFieldsStruct();
        $checkoutOrderCustomFields->setCheckoutPaymentId('foo');
        $checkoutOrderCustomFields->setIsRefundedFromHub(true);

        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setCustomFields([
            OrderService::CHECKOUT_CUSTOM_FIELDS => $checkoutOrderCustomFields->jsonSerialize(),
        ]);

        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId($orderId);
        $orderRefundRequest->setItems(new RefundItemRequestCollection());

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        static::expectException(CheckoutComException::class);

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentOfNullPaymentHandler(): void
    {
        $orderId = 'foo';

        $orderCurrency = new CurrencyEntity();
        $orderCurrency->setId('foo');
        $orderCurrency->setIsoCode('foo');

        $checkoutOrderCustomFields = new OrderCustomFieldsStruct();
        $checkoutOrderCustomFields->setCheckoutPaymentId('foo');

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('foo');
        $orderTransactions = new OrderTransactionCollection([$orderTransaction]);

        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setLineItems(new OrderLineItemCollection());
        $order->setSalesChannelId('foo');
        $order->setCustomFields([
            OrderService::CHECKOUT_CUSTOM_FIELDS => $checkoutOrderCustomFields->jsonSerialize(),
        ]);
        $order->setTransactions($orderTransactions);

        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId($orderId);
        $orderRefundRequest->setItems(new RefundItemRequestCollection());

        $lineItems = new LineItemCollection([new LineItem('foo', 'bar')]);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderExtractor->expects(static::once())
            ->method('extractCurrency')
            ->willReturn($orderCurrency);

        $this->refundBuilder->expects(static::once())
            ->method('buildLineItems')
            ->willReturn($lineItems);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction')
            ->willReturn($orderTransaction);

        $this->paymentMethodService->expects(static::once())
            ->method('getPaymentHandlerByOrderTransaction')
            ->willReturn(null);

        static::expectException(CheckoutComException::class);

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentOfThrowExceptionBecauseStatusCanNotRefund(): void
    {
        $orderId = 'foo';

        $orderCurrency = new CurrencyEntity();
        $orderCurrency->setId('foo');
        $orderCurrency->setIsoCode('foo');

        $checkoutOrderCustomFields = new OrderCustomFieldsStruct();
        $checkoutOrderCustomFields->setCheckoutPaymentId('foo');

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('foo');
        $orderTransactions = new OrderTransactionCollection([$orderTransaction]);

        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setLineItems(new OrderLineItemCollection());
        $order->setSalesChannelId('foo');
        $order->setCustomFields([
            OrderService::CHECKOUT_CUSTOM_FIELDS => $checkoutOrderCustomFields->jsonSerialize(),
        ]);
        $order->setTransactions($orderTransactions);

        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId($orderId);
        $orderRefundRequest->setItems(new RefundItemRequestCollection());

        $paymentHandler = $this->createMock(PaymentHandler::class);

        $lineItems = new LineItemCollection([new LineItem('foo', 'bar')]);

        $payment = new Payment();

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderExtractor->expects(static::once())
            ->method('extractCurrency')
            ->willReturn($orderCurrency);

        $this->refundBuilder->expects(static::once())
            ->method('buildLineItems')
            ->willReturn($lineItems);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction')
            ->willReturn($orderTransaction);

        $this->paymentMethodService->expects(static::once())
            ->method('getPaymentHandlerByOrderTransaction')
            ->willReturn($paymentHandler);

        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentDetails')
            ->willReturn($payment);

        static::expectException(CheckoutComException::class);

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentOfThrowExceptionBecauseCallRefundFailed(): void
    {
        $orderId = 'foo';

        $orderCurrency = new CurrencyEntity();
        $orderCurrency->setId('foo');
        $orderCurrency->setIsoCode('foo');

        $checkoutOrderCustomFields = new OrderCustomFieldsStruct();
        $checkoutOrderCustomFields->setCheckoutPaymentId('foo');

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('foo');
        $orderTransactions = new OrderTransactionCollection([$orderTransaction]);

        $orderDelivery = new OrderDeliveryEntity();
        $orderDelivery->setId('foo');
        $orderDelivery->setShippingCosts(
            new CalculatedPrice(
                5.5,
                5.5,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
            )
        );

        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setLineItems(new OrderLineItemCollection());
        $order->setSalesChannelId('foo');
        $order->setCustomFields([
            OrderService::CHECKOUT_CUSTOM_FIELDS => $checkoutOrderCustomFields->jsonSerialize(),
        ]);
        $order->setTransactions($orderTransactions);
        $order->setDeliveries(new OrderDeliveryCollection([$orderDelivery]));
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

        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId($orderId);
        $orderRefundRequest->setItems(new RefundItemRequestCollection());

        $paymentHandler = $this->createMock(PaymentHandler::class);

        $lineItems = new LineItemCollection([new LineItem('foo', 'bar')]);

        $payment = new Payment();
        $payment->assign([
            'status' => CheckoutPaymentService::STATUS_CAPTURED,
        ]);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderExtractor->expects(static::once())
            ->method('extractCurrency')
            ->willReturn($orderCurrency);

        $this->refundBuilder->expects(static::once())
            ->method('buildLineItems')
            ->willReturn($lineItems);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction')
            ->willReturn($orderTransaction);

        $this->paymentMethodService->expects(static::once())
            ->method('getPaymentHandlerByOrderTransaction')
            ->willReturn($paymentHandler);

        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentDetails')
            ->willReturn($payment);

        $paymentHandler->expects(static::once())
            ->method('refundPayment')
            ->willThrowException(new Exception('foo'));

        static::expectException(CheckoutComException::class);

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentSuccessful(): void
    {
        $orderId = 'foo';

        $orderCurrency = new CurrencyEntity();
        $orderCurrency->setId('foo');
        $orderCurrency->setIsoCode('foo');

        $checkoutOrderCustomFields = new OrderCustomFieldsStruct();
        $checkoutOrderCustomFields->setCheckoutPaymentId('foo');

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('foo');
        $orderTransactions = new OrderTransactionCollection([$orderTransaction]);

        $orderDelivery = new OrderDeliveryEntity();
        $orderDelivery->setId('foo');
        $orderDelivery->setShippingCosts(
            new CalculatedPrice(
                5.5,
                5.5,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
            )
        );

        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setLineItems(new OrderLineItemCollection());
        $order->setSalesChannelId('foo');
        $order->setCustomFields([
            OrderService::CHECKOUT_CUSTOM_FIELDS => $checkoutOrderCustomFields->jsonSerialize(),
        ]);
        $order->setTransactions($orderTransactions);
        $order->setDeliveries(new OrderDeliveryCollection([$orderDelivery]));
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

        $orderRefundRequest = new OrderRefundRequest();
        $orderRefundRequest->setOrderId($orderId);
        $orderRefundRequest->setItems(new RefundItemRequestCollection());

        $paymentHandler = $this->createMock(PaymentHandler::class);

        $lineItemPayload = new LineItemPayload();
        $lineItemPayload->setProductId('foo');

        $lineItem = new LineItem('foo', 'bar');
        $lineItem->setPayload([
            RefundBuilder::LINE_ITEM_PAYLOAD => $lineItemPayload->jsonSerialize(),
        ]);
        $lineItems = new LineItemCollection([
            $lineItem,
        ]);

        $payment = new Payment();
        $payment->assign([
            'status' => CheckoutPaymentService::STATUS_CAPTURED,
        ]);

        $this->orderService->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->orderExtractor->expects(static::once())
            ->method('extractCurrency')
            ->willReturn($orderCurrency);

        $this->refundBuilder->expects(static::once())
            ->method('buildLineItems')
            ->willReturn($lineItems);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction')
            ->willReturn($orderTransaction);

        $this->paymentMethodService->expects(static::once())
            ->method('getPaymentHandlerByOrderTransaction')
            ->willReturn($paymentHandler);

        $this->checkoutPaymentService->expects(static::once())
            ->method('getPaymentDetails')
            ->willReturn($payment);

        $paymentHandler->expects(static::once())
            ->method('refundPayment');

        $this->settingsFactory->expects(static::once())
            ->method('getSettings');

        $this->orderConverter->expects(static::once())
            ->method('convertToOrder');

        $this->orderService->expects(static::once())
            ->method('updateOrder');

        $this->refundBuilder->expects(static::once())
            ->method('getCheckoutLineItemPayload')
            ->willReturn($lineItemPayload);

        $this->productService->expects(static::once())
            ->method('increaseStock');

        $this->orderTransactionService->expects(static::once())
            ->method('processTransition');

        $this->orderService->expects(static::once())
            ->method('processTransition');

        $this->paymentRefundFacade->refundPayment(
            $orderRefundRequest,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentByWebhookOfNullCheckoutPaymentId(): void
    {
        $orderId = 'foo';

        $order = new OrderEntity();
        $order->setId($orderId);

        $receiveData = new WebhookReceiveDataStruct();

        static::expectException(CheckoutPaymentIdNotFoundException::class);

        $this->paymentRefundFacade->refundPaymentByWebhook(
            $order,
            $receiveData,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentByWebhookOfOrderTotalAmountLessThanWebhookRequestRefundAmount(): void
    {
        $orderId = 'foo';

        $checkoutOrderCustomFields = new OrderCustomFieldsStruct();
        $checkoutOrderCustomFields->setCheckoutPaymentId('foo');
        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setCustomFields([
            OrderService::CHECKOUT_CUSTOM_FIELDS => $checkoutOrderCustomFields->jsonSerialize(),
        ]);
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

        $receiveData = new WebhookReceiveDataStruct();

        $lineItem = new LineItem('foo', 'bar');
        $lineItem->setPrice(
            new CalculatedPrice(
                -500,
                -500,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                1,
            )
        );
        $requestLineItems = new LineItemCollection([$lineItem]);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction');

        $this->refundBuilder->expects(static::once())
            ->method('buildLineItemsForWebhook')
            ->willReturn($requestLineItems);

        static::expectException(CheckoutComException::class);

        $this->paymentRefundFacade->refundPaymentByWebhook(
            $order,
            $receiveData,
            $this->salesChannelContext->getContext()
        );
    }

    public function testRefundPaymentByWebhookSuccessful(): void
    {
        $orderId = 'foo';

        $checkoutOrderCustomFields = new OrderCustomFieldsStruct();
        $checkoutOrderCustomFields->setCheckoutPaymentId('foo');
        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setSalesChannelId('foo');
        $order->setCustomFields([
            OrderService::CHECKOUT_CUSTOM_FIELDS => $checkoutOrderCustomFields->jsonSerialize(),
        ]);
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

        $receiveData = new WebhookReceiveDataStruct();

        $lineItem = new LineItem('foo', 'bar');
        $lineItem->setPrice(
            new CalculatedPrice(
                -5.5,
                -5.5,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                1,
            )
        );
        $requestLineItems = new LineItemCollection([$lineItem]);

        $settings = $this->createMock(SettingStruct::class);

        $this->orderExtractor->expects(static::once())
            ->method('extractLatestOrderTransaction');

        $this->refundBuilder->expects(static::once())
            ->method('buildLineItemsForWebhook')
            ->willReturn($requestLineItems);

        $this->settingsFactory->expects(static::once())
            ->method('getSettings')
            ->willReturn($settings);

        $this->orderConverter->expects(static::once())
            ->method('convertToOrder');

        $this->orderService->expects(static::once())
            ->method('updateOrder');

        $this->orderService->expects(static::once())
            ->method('updateCheckoutCustomFields')
            ->with(
                static::isInstanceOf(OrderEntity::class),
                static::isInstanceOf(OrderCustomFieldsStruct::class),
                static::isInstanceOf(Context::class)
            );

        $this->orderTransactionService->expects(static::once())
            ->method('processTransition')
            ->with(
                static::isInstanceOf(OrderTransactionEntity::class),
                CheckoutPaymentService::STATUS_REFUNDED,
                static::isInstanceOf(Context::class)
            );

        $this->orderService->expects(static::once())
            ->method('processTransition')
            ->with(
                static::isInstanceOf(OrderEntity::class),
                $settings,
                CheckoutPaymentService::STATUS_REFUNDED,
                static::isInstanceOf(Context::class)
            );

        $this->paymentRefundFacade->refundPaymentByWebhook(
            $order,
            $receiveData,
            $this->salesChannelContext->getContext()
        );
    }
}
