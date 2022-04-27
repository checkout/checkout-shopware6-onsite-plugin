<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler;

use Checkout\Payments\PaymentRequest;
use Checkout\Payments\ThreeDsRequest;
use CheckoutCom\Shopware6\Facade\PaymentFinalizeFacade;
use CheckoutCom\Shopware6\Facade\PaymentPayFacade;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutTokenService;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

abstract class PaymentHandler implements AsynchronousPaymentHandlerInterface
{
    protected LoggerInterface $logger;

    protected TranslatorInterface $translator;

    protected DataValidator $dataValidator;

    protected CheckoutTokenService $checkoutTokenService;

    protected PaymentPayFacade $paymentPayFacade;

    protected PaymentFinalizeFacade $paymentFinalizeFacade;

    public function __construct(
        LoggerInterface $logger,
        TranslatorInterface $translator,
        DataValidator $dataValidator,
        CheckoutTokenService $checkoutTokenService,
        PaymentPayFacade $paymentPayFacade,
        PaymentFinalizeFacade $paymentFinalizeFacade
    ) {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->dataValidator = $dataValidator;
        $this->checkoutTokenService = $checkoutTokenService;
        $this->paymentPayFacade = $paymentPayFacade;
        $this->paymentFinalizeFacade = $paymentFinalizeFacade;
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
        CustomerEntity $customer,
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
            $this->paymentFinalizeFacade->finalize($transaction, $salesChannelContext);
        } catch (AsyncPaymentFinalizeException $ex) {
            // We catch AsyncPaymentFinalizeException, log and throw it again
            $this->logger->error(
                sprintf('Error finalizing with order %s, Error: %s', $transaction->getOrder()->getOrderNumber(), $ex->getMessage())
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
     * Enable 3DS for payment request
     */
    public function enableThreeDsRequest(PaymentRequest $paymentRequest): void
    {
        $threeDs = new ThreeDsRequest();
        $threeDs->enabled = true;
        $paymentRequest->three_ds = $threeDs;
    }
}
