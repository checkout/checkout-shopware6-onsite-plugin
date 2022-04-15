<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Event;

use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Payment;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Symfony\Contracts\EventDispatcher\Event;

class CheckoutFinalizeStatusEvent extends Event
{
    private OrderEntity $order;

    private Payment $payment;

    private ?string $paymentStatus;

    public function __construct(OrderEntity $order, Payment $payment, ?string $paymentStatus)
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->paymentStatus = $paymentStatus;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('order', new EntityType(OrderDefinition::class))
            ->add('paymentStatus', new ScalarValueType(ScalarValueType::TYPE_STRING));
    }
}
