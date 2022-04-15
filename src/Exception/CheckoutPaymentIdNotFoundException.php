<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CheckoutPaymentIdNotFoundException extends ShopwareHttpException
{
    public function __construct(OrderEntity $order, array $parameters = [])
    {
        $message = sprintf(
            'The payment ID from checkout.com could not be found within the order ID: %s, order number: %s',
            $order->getId(),
            $order->getOrderNumber()
        );
        parent::__construct($message, $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_PAYMENT_ID_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
