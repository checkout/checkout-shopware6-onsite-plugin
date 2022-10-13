<?php declare(strict_types=1);

namespace Cko\Shopware6\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CheckoutComException extends ShopwareHttpException
{
    public function __construct(string $message, array $parameters = [])
    {
        parent::__construct($message ?? 'Checkout exception', $parameters);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_EXCEPTION';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
