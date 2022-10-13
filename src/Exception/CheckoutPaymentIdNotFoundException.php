<?php declare(strict_types=1);

namespace Cko\Shopware6\Exception;

use Shopware\Core\Checkout\Order\OrderEntity;

class CheckoutPaymentIdNotFoundException extends CheckoutComException
{
    public function __construct(OrderEntity $order, array $parameters = [])
    {
        parent::__construct(sprintf(
            'The payment ID from checkout.com could not be found within the order ID: %s, order number: %s',
            $order->getId(),
            $order->getOrderNumber()
        ), $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_PAYMENT_ID_NOT_FOUND';
    }
}
