<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CheckoutInvalidTokenException extends ShopwareHttpException
{
    public function __construct(string $paymentType = '', array $parameters = [])
    {
        $message = sprintf('Invalid token for payment type: %s', $paymentType);

        parent::__construct($message, $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_INVALID_REQUEST_TOKEN_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
