<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CheckoutPaymentIdNotFoundException extends ShopwareHttpException
{
    public function __construct(string $orderNumber, array $parameters = [])
    {
        $message = sprintf('The payment ID from checkout.com could not be found within the order number: %s', $orderNumber);
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
