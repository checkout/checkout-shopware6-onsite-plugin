<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler;

use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\RequestIdSource;
use Checkout\Payments\ThreeDsRequest;
use CheckoutCom\Shopware6\Facade\PaymentFinalizeFacade;
use CheckoutCom\Shopware6\Facade\PaymentPayFacade;
use CheckoutCom\Shopware6\Factory\SettingsFactory;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutSourceService;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutTokenService;
use CheckoutCom\Shopware6\Service\ContextService;
use CheckoutCom\Shopware6\Service\CountryService;
use CheckoutCom\Shopware6\Service\Extractor\AbstractOrderExtractor;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingOptionCollection;
use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingOptionStruct;
use CheckoutCom\Shopware6\Struct\DirectPay\AbstractShippingPayloadStruct;
use CheckoutCom\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\Currency\CurrencyFormatter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

abstract class PaymentHandler implements AsynchronousPaymentHandlerInterface
{
    protected LoggerInterface $logger;

    protected CountryService $countryService;

    protected ContextService $contextService;

    protected TranslatorInterface $translator;

    protected DataValidator $dataValidator;

    protected CurrencyFormatter $currencyFormatter;

    protected SystemConfigService $systemConfigService;

    protected AbstractOrderExtractor $orderExtractor;

    protected CheckoutTokenService $checkoutTokenService;

    protected CheckoutSourceService $checkoutSourceService;

    protected CheckoutPaymentService $checkoutPaymentService;

    protected PaymentPayFacade $paymentPayFacade;

    protected PaymentFinalizeFacade $paymentFinalizeFacade;

    protected SettingsFactory $settingsFactory;

    public function __construct(
        TranslatorInterface $translator,
        DataValidator $dataValidator,
        CurrencyFormatter $currencyFormatter,
        SystemConfigService $systemConfigService
    ) {
        $this->translator = $translator;
        $this->dataValidator = $dataValidator;
        $this->currencyFormatter = $currencyFormatter;
        $this->systemConfigService = $systemConfigService;
    }

    public function setServices(
        LoggerInterface $logger,
        CountryService $countryService,
        ContextService $contextService,
        AbstractOrderExtractor $orderExtractor,
        CheckoutTokenService $checkoutTokenService,
        CheckoutSourceService $checkoutSourceService,
        CheckoutPaymentService $checkoutPaymentService,
        PaymentPayFacade $paymentPayFacade,
        PaymentFinalizeFacade $paymentFinalizeFacade,
        SettingsFactory $settingsFactory
    ): void {
        $this->logger = $logger;
        $this->countryService = $countryService;
        $this->contextService = $contextService;
        $this->orderExtractor = $orderExtractor;
        $this->checkoutTokenService = $checkoutTokenService;
        $this->checkoutSourceService = $checkoutSourceService;
        $this->checkoutPaymentService = $checkoutPaymentService;
        $this->paymentPayFacade = $paymentPayFacade;
        $this->paymentFinalizeFacade = $paymentFinalizeFacade;
        $this->settingsFactory = $settingsFactory;
    }

    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNameCollection = new DisplayNameTranslationCollection();

        // Support languages
        $languages = ['de-DE', 'en-GB'];

        foreach ($languages as $lang) {
            $displayNameCollection->addLangData($lang, $this->translator->trans($this->getSnippetKey(), [], null, $lang));
        }

        return $displayNameCollection;
    }

    /**
     * Get snippet lang key
     */
    abstract public function getSnippetKey(): string;

    /**
     * Get checkout.com payment method type
     */
    abstract public static function getPaymentMethodType(): string;

    /**
     * Each payment method has to implement this method to prepare data for the checkout.com payment request
     * Each payment method will have different source request data
     * Can modify the Checkout.com PaymentRequest object here
     *
     * @throws ConstraintViolationException
     * @throws Exception
     */
    abstract public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SalesChannelContext $context
    ): PaymentRequest;

    public function getClassName(): string
    {
        return static::class;
    }

    /**
     * The pay method will be called after a customer has completed the order.
     * We will create a payment via the checkout.com API and store the data in the custom fields of the order
     * Maybe we will redirect to external payment (Checkout.com) and redirect back to our the shopware @finalize method.
     *
     * @throw ConstraintViolationException
     * @throw AsyncPaymentProcessException
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $this->logger->info(
            sprintf('Starting pay with order number: %s', $transaction->getOrder()->getOrderNumber()),
            [
                'orderNumber' => $transaction->getOrder()->getOrderNumber(),
                'methodType' => static::getPaymentMethodType(),
                'salesChannelName' => $salesChannelContext->getSalesChannel()->getName(),
                'salesChannelId' => $salesChannelContext->getSalesChannel()->getId(),
                'cart' => [
                    'amount' => $transaction->getOrder()->getAmountTotal(),
                ],
            ]
        );

        try {
            $payment = $this->paymentPayFacade->pay(
                $this,
                $transaction,
                $dataBag,
                $salesChannelContext,
            );
        } catch (ConstraintViolationException $exception) {
            // This case only happens when the data request is not valid.
            // Actually, Headless support will throw this exception
            // If Storefront throws this exception, it means the data we sent to the server is not valid
            // need to check it manually on our side
            $this->logger->error(sprintf('Error when starting payment with violation error:  %s', $exception->getMessage()), [
                'function' => 'pay',
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Error when starting payment: %s', $exception->getMessage()),
                [
                    'function' => 'pay',
                ]
            );

            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }

        return new RedirectResponse($payment->getRedirectUrl());
    }

    /**
     * This method will finalize the order
     * We will update order/order_transaction status depend on checkout.com payment status
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        try {
            $this->paymentFinalizeFacade->finalize($this, $transaction, $salesChannelContext);
        } catch (AsyncPaymentFinalizeException $ex) {
            // We catch AsyncPaymentFinalizeException, log and throw it again
            $this->logger->error(
                sprintf('Error finalizing with order %s, Error: %s', $transaction->getOrder()->getOrderNumber(), $ex->getMessage())
            );

            throw $ex;
        } catch (CustomerCanceledAsyncPaymentException $ex) {
            // We catch CustomerCanceledAsyncPaymentException, log and throw it again
            $this->logger->error(
                sprintf('Payment of order %s has been canceled', $transaction->getOrder()->getOrderNumber())
            );

            throw $ex;
        } catch (Throwable $ex) {
            // We catch all other exceptions and throw AsyncPaymentFinalizeException
            $this->logger->error(
                sprintf('Unknown Error when finalizing order number %s, Error: %s', $transaction->getOrder()->getOrderNumber(), $ex->getMessage())
            );

            throw new AsyncPaymentFinalizeException(
                $transaction->getOrderTransaction()->getId(),
                'Internal error exception, view the log for more information'
            );
        }
    }

    /**
     * @throws Exception
     */
    public function capturePayment(string $checkoutPaymentId, OrderEntity $order): void
    {
        // We capture the payment from the Checkout.com API, the CheckoutApiException will be thrown if the API is failed
        $this->checkoutPaymentService->capturePayment($checkoutPaymentId, $order->getSalesChannelId());
    }

    /**
     * @throws Exception
     */
    public function voidPayment(string $checkoutPaymentId, OrderEntity $order): void
    {
        // We void the payment from the Checkout.com API, the CheckoutApiException will be thrown if the API is failed
        $this->checkoutPaymentService->voidPayment($checkoutPaymentId, $order->getSalesChannelId());
    }

    public function captureWhenFinalize(): bool
    {
        return true;
    }

    public function shouldManualCapture(): bool
    {
        return false;
    }

    public function shouldManualVoid(): bool
    {
        return false;
    }

    public function shouldCaptureAfterShipping(): bool
    {
        return false;
    }

    /**
     * Enable 3DS for payment request
     */
    public function enableThreeDsRequest(PaymentRequest $paymentRequest, ?string $salesChannelId = null): void
    {
        if (!$this->settingsFactory->get3dSecureConfig($salesChannelId)) {
            return;
        }

        $threeDs = new ThreeDsRequest();
        $threeDs->enabled = true;
        $paymentRequest->three_ds = $threeDs;
    }

    /**
     * Get RequestIdSource instance from request data bag if the DataBag has `sourceId` key
     */
    public function getRequestIdSource(RequestDataBag $dataBag): ?RequestIdSource
    {
        $sourceId = RequestUtil::getSourceIdPayment($dataBag);
        if (!\is_string($sourceId)) {
            return null;
        }

        $requestIdSource = new RequestIdSource();
        $requestIdSource->id = $sourceId;

        return $requestIdSource;
    }

    /**
     * Get direct shipping options collection
     *
     * @throws Exception
     */
    public function getDirectShippingOptions(): AbstractShippingOptionCollection
    {
        throw new Exception(sprintf('getDirectShippingOptions function are not supported by: %s', $this->getClassName()));
    }

    /**
     * Format direct shipping for each shipping option
     *
     * @throws Exception
     */
    public function formatDirectShippingOption(
        ShippingMethodEntity $shippingMethodEntity,
        float $shippingCostsPrice,
        SalesChannelContext $context
    ): AbstractShippingOptionStruct {
        throw new Exception(sprintf('formatDirectShippingOption function are not supported by: %s', $this->getClassName()));
    }

    /**
     * Get shipping payload data for direct payment
     *
     * @throws Exception
     */
    public function getDirectShippingPayload(
        ?AbstractShippingOptionCollection $shippingMethods,
        DirectPayCartStruct $directPayCart,
        SalesChannelContext $context
    ): AbstractShippingPayloadStruct {
        throw new Exception(sprintf('getDirectShippingPayload function are not supported by: %s', $this->getClassName()));
    }

    protected function getShopName(SalesChannelContext $context): string
    {
        return $this->systemConfigService->getString('core.basicInformation.shopName', $context->getSalesChannel()->getId());
    }
}
