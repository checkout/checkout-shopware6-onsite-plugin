<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Order;

use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\Order\OrderService;
use CheckoutCom\Shopware6\Service\Transition\OrderTransitionService;
use CheckoutCom\Shopware6\Struct\SettingStruct;
use CheckoutCom\Shopware6\Tests\Fakes\FakeEntityRepository;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderServiceTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    private FakeEntityRepository $orderRepository;

    /**
     * @var MockObject|OrderTransitionService
     */
    private $orderTransitionService;

    private OrderService $orderService;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->orderRepository = new FakeEntityRepository(new OrderDefinition());
        $this->orderTransitionService = $this->createMock(OrderTransitionService::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->orderService = new OrderService(
            $this->createMock(LoggerService::class),
            $this->orderRepository,
            $this->orderTransitionService
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->orderService->getDecorated();
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

        $this->orderService->updateCheckoutCustomFields($order, $checkoutOrderCustomFields, $this->salesChannelContext);

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

        $this->orderTransitionService->expects(static::exactly($expectThrowException ? 0 : 1))
            ->method('setTransitionState');

        $settings = $this->createMock(SettingStruct::class);

        $this->orderService->processTransition($this->getOrder(), $settings, $checkoutPaymentStatus, $this->salesChannelContext->getContext());
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
            'Test transition order success with checkout status is pending' => [
                false,
                CheckoutPaymentService::STATUS_PENDING,
            ],
        ];
    }
}
