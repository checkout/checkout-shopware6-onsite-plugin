<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

class OrderTransactionNotFoundException extends CheckoutComException
{
    private string $orderTransactionId;

    public function __construct(string $orderTransactionId)
    {
        parent::__construct(
            'Order transaction with id "{{ orderTransactionId }}" not found.',
            ['orderTransactionId' => $orderTransactionId]
        );

        $this->orderTransactionId = $orderTransactionId;
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM__ORDER_TRANSACTION_NOT_FOUND';
    }

    public function getOrderTransactionId(): string
    {
        return $this->orderTransactionId;
    }
}
