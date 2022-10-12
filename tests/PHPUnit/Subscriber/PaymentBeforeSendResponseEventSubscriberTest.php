<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Subscriber;

use Cko\Shopware6\Helper\RequestUtil;
use Cko\Shopware6\Service\LoggerService;
use Cko\Shopware6\Service\Order\OrderService;
use Cko\Shopware6\Subscriber\PaymentBeforeSendResponseEventSubscriber;
use Cko\Shopware6\Tests\Traits\ContextTrait;
use Cko\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Event\BeforeSendResponseEvent;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class PaymentBeforeSendResponseEventSubscriberTest extends TestCase
{
    use OrderTrait;
    use ContextTrait;

    private PaymentBeforeSendResponseEventSubscriber $subscriber;

    /**
     * @var MockObject|RouterInterface
     */
    private $router;

    /**
     * @var MockObject|LoggerService
     */
    private $logger;

    /**
     * @var MockObject|OrderService
     */
    private $orderService;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->salesChannelContext = $this->getSaleChannelContext($this);
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerService::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->subscriber = new PaymentBeforeSendResponseEventSubscriber(
            $this->router,
            $this->logger,
            $this->orderService
        );
    }

    public function testListeningOnCorrectEvent(): void
    {
        static::assertArrayHasKey(BeforeSendResponseEvent::class, PaymentBeforeSendResponseEventSubscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider onBeforeSendResponseProvider
     */
    public function testOnBeforeSendResponse(
        bool $isCheckoutPayment,
        bool $isDataJson,
        bool $hasError,
        ?string $orderId,
        ?string $lastOrderId,
        ?string $checkoutPaymentId
    ): void {
        $inputBag = new InputBag();
        $inputBag->set($isCheckoutPayment ? RequestUtil::DATA_BAG_KEY : 'any key', [
            RequestUtil::DATA_JSON => $isDataJson,
        ]);

        $parameterBag = new ParameterBag();
        $parameterBag->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $this->salesChannelContext->getContext());

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $request->request = $inputBag;
        $request->attributes = $parameterBag;
        $event = new BeforeSendResponseEvent(
            $request,
            $response
        );

        $this->router->method('generate')
            ->willReturn('foo');

        if (!$isCheckoutPayment || !$isDataJson) {
            $request->expects(static::never())
                ->method('get');

            $this->subscriber->onBeforeSendResponse($event);
        } else {
            $success = false;
            $request->expects(static::once())
                ->method('get')
                ->willReturn($orderId);

            $this->orderService->expects(static::exactly($orderId === null ? 1 : 0))
                ->method('getRequestLastOrderId')
                ->willReturn($lastOrderId);

            if ($lastOrderId === null && $orderId === null) {
                $this->logger->expects(static::once())
                    ->method('critical');
            } else {
                $order = $this->getOrder($checkoutPaymentId);
                if ($hasError) {
                    $this->orderService->expects(static::once())
                        ->method('getOrder')
                        ->willThrowException(new \Exception());

                    $this->logger->expects(static::once())
                        ->method('critical');
                } else {
                    $this->orderService->expects(static::once())
                        ->method('getOrder')
                        ->willReturn($order);

                    if ($checkoutPaymentId) {
                        $success = true;
                        $event->setResponse($this->createMock(RedirectResponse::class));
                    }
                }
            }

            $this->subscriber->onBeforeSendResponse($event);

            static::assertInstanceOf(JsonResponse::class, $event->getResponse());
            $content = json_decode($event->getResponse()->getContent(), true);
            static::assertSame($success, $content['success']);
        }
    }

    public function onBeforeSendResponseProvider(): array
    {
        return [
            'Test is not checkout payment' => [
                false,
                true,
                false,
                null,
                null,
                null,
            ],
            'Test is not data json' => [
                true,
                false,
                false,
                null,
                null,
                null,
            ],
            'Test is coming from edit page but does not have checkout.com payment id' => [
                true,
                true,
                false,
                '123',
                null,
                null,
            ],
            'Test is coming from edit page successful' => [
                true,
                true,
                false,
                '123',
                null,
                '12345',
            ],
            'Test is coming from checkout page but the last order id is empty' => [
                true,
                true,
                false,
                null,
                null,
                null,
            ],
            'Test is coming from checkout page but does not have checkout.com payment id' => [
                true,
                true,
                false,
                null,
                '12345',
                null,
            ],
            'Test is coming from checkout page has error, expect throw error' => [
                true,
                true,
                true,
                null,
                '123',
                '12345',
            ],
            'Test is coming from checkout page successful' => [
                true,
                true,
                false,
                null,
                '123',
                '12345',
            ],
        ];
    }
}
