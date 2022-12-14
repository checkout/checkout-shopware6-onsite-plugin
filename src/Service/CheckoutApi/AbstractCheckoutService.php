<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\CheckoutApi;

use Checkout\CheckoutApiException;
use Cko\Shopware6\Factory\CheckoutApiFactory;
use Psr\Log\LoggerInterface;

abstract class AbstractCheckoutService
{
    public const ERROR_TOKEN_INVALID = 'token_invalid';

    protected LoggerInterface $logger;

    protected CheckoutApiFactory $checkoutApiFactory;

    public function __construct(LoggerInterface $logger, CheckoutApiFactory $checkoutApiFactory)
    {
        $this->logger = $logger;
        $this->checkoutApiFactory = $checkoutApiFactory;
    }

    protected function logMessage(CheckoutApiException $exception, string $functionName, array $parameters = []): void
    {
        $this->logger->critical(
            sprintf('Call checkout api service, Error: %s', $exception->getMessage()),
            array_merge(
                $parameters,
                [
                    'details' => $exception->error_details ?? [],
                    'meta_data' => get_object_vars($exception->http_metadata),
                    'function' => $functionName,
                ]
            )
        );
    }
}
