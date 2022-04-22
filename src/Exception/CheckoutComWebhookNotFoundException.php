<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CheckoutComWebhookNotFoundException extends ShopwareHttpException
{
    public function __construct(string $webhookId)
    {
        parent::__construct(sprintf('The webhook from checkout.com could not be found with ID: %s', $webhookId));
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT_COM_WEBHOOK_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
