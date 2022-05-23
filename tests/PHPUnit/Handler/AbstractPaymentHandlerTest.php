<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Handler;

use CheckoutCom\Shopware6\Facade\PaymentFinalizeFacade;
use CheckoutCom\Shopware6\Facade\PaymentPayFacade;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutSourceService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutTokenService;
use CheckoutCom\Shopware6\Service\Extractor\AbstractOrderExtractor;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Struct\PaymentHandler\HandlerPrepareProcessStruct;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\Currency\CurrencyFormatter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

abstract class AbstractPaymentHandlerTest extends TestCase
{
    use OrderTrait;
    use ContextTrait;

    /**
     * @var AbstractOrderExtractor|MockObject
     */
    protected $orderExtractor;

    /**
     * @var CheckoutTokenService|MockObject
     */
    protected $checkoutTokenService;

    /**
     * @var CheckoutSourceService|MockObject
     */
    protected $checkoutSourceService;

    /**
     * @var CheckoutPaymentService|MockObject
     */
    protected $checkoutPaymentService;

    /**
     * @var PaymentPayFacade|MockObject
     */
    protected $paymentPayFacade;

    /**
     * @var PaymentFinalizeFacade|MockObject
     */
    protected $paymentFinalizeFacade;

    /**
     * @var TranslatorInterface|MockObject
     */
    protected $translator;

    /**
     * @var DataValidator|MockObject
     */
    protected $dataValidator;

    /**
     * @var CurrencyFormatter|MockObject
     */
    protected $currencyFormatter;

    /**
     * @var SystemConfigService|MockObject
     */
    protected $systemConfigService;

    /**
     * @var MockObject|SalesChannelContext
     */
    protected $saleChannelContext;

    protected PaymentHandler $paymentHandler;

    public function setUp(): void
    {
        $this->saleChannelContext = $this->getSaleChannelContext($this);
        $this->orderExtractor = $this->createMock(AbstractOrderExtractor::class);
        $this->checkoutTokenService = $this->createMock(CheckoutTokenService::class);
        $this->checkoutSourceService = $this->createMock(CheckoutSourceService::class);
        $this->checkoutPaymentService = $this->createMock(CheckoutPaymentService::class);
        $this->paymentPayFacade = $this->createMock(PaymentPayFacade::class);
        $this->paymentFinalizeFacade = $this->createMock(PaymentFinalizeFacade::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->dataValidator = $this->createMock(DataValidator::class);
        $this->currencyFormatter = $this->createMock(CurrencyFormatter::class);
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
    }

    public function setServices(): void
    {
        $this->paymentHandler->setServices(
            $this->createMock(LoggerService::class),
            $this->orderExtractor,
            $this->checkoutTokenService,
            $this->checkoutSourceService,
            $this->checkoutPaymentService,
            $this->paymentPayFacade,
            $this->paymentFinalizeFacade
        );
    }

    /**
     * @dataProvider payProvider
     */
    public function testPay(?Throwable $exception): void
    {
        $payMethodMocker = $this->paymentPayFacade
            ->expects(static::once())
            ->method('pay');

        if ($exception !== null) {
            $payMethodMocker->will(static::throwException($exception));
            static::expectException(AsyncPaymentProcessException::class);
        } else {
            $payment = new HandlerPrepareProcessStruct('redirect url', 'payment id');
            $payMethodMocker->willReturn($payment);
        }

        $payProcess = $this->paymentHandler->pay(
            new AsyncPaymentTransactionStruct(
                $this->getOrderTransaction(),
                $this->getOrder(),
                'example.com'
            ),
            $this->createMock(RequestDataBag::class),
            $this->saleChannelContext
        );

        static::assertInstanceOf(RedirectResponse::class, $payProcess);
    }

    /**
     * @dataProvider finalizeProvider
     */
    public function testFinalize(?Throwable $exception): void
    {
        $finalizeMethodMocker = $this->paymentFinalizeFacade->expects(static::once())
            ->method('finalize');

        if ($exception !== null) {
            $finalizeMethodMocker->will(static::throwException($exception));

            if ($exception instanceof CustomerCanceledAsyncPaymentException) {
                static::expectException(CustomerCanceledAsyncPaymentException::class);
            } else {
                static::expectException(AsyncPaymentFinalizeException::class);
            }
        }

        $this->paymentHandler->finalize(
            new AsyncPaymentTransactionStruct(
                $this->getOrderTransaction(),
                $this->getOrder(),
                'example.com'
            ),
            $this->createMock(Request::class),
            $this->saleChannelContext
        );
    }

    public function payProvider(): array
    {
        return [
            'Test must throw exception' => [
                new OrderNotFoundException('test'),
            ],
            'Test pay successful' => [
                null,
            ],
        ];
    }

    public function finalizeProvider(): array
    {
        return [
            'Test must throw exception' => [
                new OrderNotFoundException('test'),
            ],
            'Test throw AsyncPaymentFinalizeException' => [
                new AsyncPaymentFinalizeException('bar', 'foo'),
            ],
            'Test throw CustomerCanceledAsyncPaymentException' => [
                new CustomerCanceledAsyncPaymentException('bar', 'foo'),
            ],
            'Test pay successful' => [
                null,
            ],
        ];
    }

    protected function getRequestBag($token = null): RequestDataBag
    {
        $paymentDetails = new RequestDataBag();
        $paymentDetails->set(RequestUtil::DATA_TOKEN, $token);
        $requestBag = new RequestDataBag();
        $requestBag->set(RequestUtil::DATA_BAG_KEY, $paymentDetails);

        return $requestBag;
    }
}
