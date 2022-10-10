<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

class OrderNotFoundException extends CheckoutComException
{
    private string $orderId;

    public function __construct(string $orderId)
    {
        parent::__construct(
            'Order with id "{{ orderId }}" not found.',
            ['orderId' => $orderId]
        );

        $this->orderId = $orderId;
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM__ORDER_NOT_FOUND';
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
