<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Event;

use Checkout\Payments\Previous\PaymentRequest;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\ObjectType;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class CheckoutRequestPaymentEvent extends Event
{
    private PaymentRequest $paymentRequest;

    private PaymentHandler $paymentHandler;

    private AsyncPaymentTransactionStruct $transaction;

    private SalesChannelContext $salesChannelContext;

    public function __construct(PaymentRequest $paymentRequest, PaymentHandler $paymentHandler, AsyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext)
    {
        $this->paymentRequest = $paymentRequest;
        $this->paymentHandler = $paymentHandler;
        $this->transaction = $transaction;
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getPaymentRequest(): PaymentRequest
    {
        return $this->paymentRequest;
    }

    public function getPaymentHandler(): PaymentHandler
    {
        return $this->paymentHandler;
    }

    public function getTransaction(): AsyncPaymentTransactionStruct
    {
        return $this->transaction;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('paymentRequest', new ObjectType());
    }
}
