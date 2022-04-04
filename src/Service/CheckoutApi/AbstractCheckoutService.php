<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\CheckoutApi;

use Checkout\CheckoutApiException;
use CheckoutCom\Shopware6\Factory\CheckoutApiFactory;
use Psr\Log\LoggerInterface;

abstract class AbstractCheckoutService
{
    protected LoggerInterface $logger;

    protected CheckoutApiFactory $checkoutApiFactory;

    public function __construct(LoggerInterface $logger, CheckoutApiFactory $checkoutApiFactory)
    {
        $this->logger = $logger;
        $this->checkoutApiFactory = $checkoutApiFactory;
    }

    protected function modifyAndLogMessage(CheckoutApiException $exception, string $functionName, array $parameters = []): string
    {
        $this->logger->critical(
            sprintf('Call checkout api service, Error: %s', $exception->getMessage()),
            array_merge(
                $parameters,
                [
                    'details' => $e->error_details ?? [],
                    'function' => $functionName,
                ]
            )
        );

        return sprintf('Could not request payment method: %s, Error: %s', $functionName, $exception->getMessage());
    }
}
