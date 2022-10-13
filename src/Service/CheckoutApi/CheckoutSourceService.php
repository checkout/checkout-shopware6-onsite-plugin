<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\CheckoutApi;

use Checkout\CheckoutApiException;
use Checkout\Sources\Previous\SepaSourceRequest;
use Cko\Shopware6\Struct\CheckoutApi\Resources\Source;

class CheckoutSourceService extends AbstractCheckoutService
{
    /**
     * @throws CheckoutApiException
     */
    public function createSepaSource(SepaSourceRequest $sepaSourceRequest, string $salesChannelId): Source
    {
        $checkoutApi = $this->checkoutApiFactory->getPreviousClient($salesChannelId);

        try {
            $response = $checkoutApi->getSourcesClient()->createSepaSource($sepaSourceRequest);

            return (new Source())->assign($response);
        } catch (CheckoutApiException $e) {
            $this->logMessage($e, __FUNCTION__);

            throw $e;
        }
    }
}
