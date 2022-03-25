<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler;

use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class PaymentHandler implements AsynchronousPaymentHandlerInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get display name for the payment method at shopware website
     */
    abstract public static function getPaymentMethodDisplayName(): DisplayNameTranslationCollection;

    /**
     * Get checkout.com payment method type
     */
    abstract public static function getPaymentMethodType(): string;

    /**
     * The pay method will be called after customer completed the order.
     * We will create payment at the checkout.com API and store data in the custom fields of the order
     * Maybe we will redirect to external payment (Checkout.com) and redirect back to our the shopware @finalize method.
     *
     * @throw AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $this->logger->info(
            sprintf('Starting pay with order: %s', $transaction->getOrder()->getOrderNumber()),
            [
                'order' => $transaction->getOrder()->getOrderNumber(),
                'methodType' => $this->getPaymentMethodType(),
                'salesChannelName' => $salesChannelContext->getSalesChannel()->getName(),
                'salesChannelId' => $salesChannelContext->getSalesChannel()->getId(),
                'cart' => [
                    'amount' => $transaction->getOrder()->getAmountTotal(),
                ],
            ]
        );

        return new RedirectResponse($transaction->getReturnUrl());
    }

    /**
     * This method will finalize the order
     * We will update order/order_transaction status depend on checkout.com payment status
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        // @TODO: CC-8: Credit Card integration backend
    }
}
