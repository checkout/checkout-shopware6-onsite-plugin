<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\CheckoutApi;

use Checkout\ApiClient;
use Checkout\CheckoutConfiguration;
use Checkout\Previous\CheckoutApi;
use Checkout\Previous\CheckoutStaticKeysPreviousSdkBuilder;

class CheckoutStaticKeysSdkBuilder extends CheckoutStaticKeysPreviousSdkBuilder
{
    public function build()
    {
        $configuration = new CheckoutConfiguration($this->getSdkCredentials(), $this->environment, $this->httpClientBuilder, $this->logger);
        $apiClient = new ApiClient($configuration);

        return new CheckoutApi($apiClient, $configuration);
    }
}
