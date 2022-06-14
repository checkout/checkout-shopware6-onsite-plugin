<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\CheckoutApi;

use Checkout\CheckoutApiException;
use Checkout\Sources\SepaSourceRequest;
use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Source;

class CheckoutSourceService extends AbstractCheckoutService
{
    /**
     * @throws CheckoutApiException
     */
    public function createSepaSource(SepaSourceRequest $sepaSourceRequest, string $salesChannelId): Source
    {
        $checkoutApi = $this->checkoutApiFactory->getClient($salesChannelId);

        try {
            $response = $checkoutApi->getSourcesClient()->createSepaSource($sepaSourceRequest);

            return (new Source())->assign($response);
        } catch (CheckoutApiException $e) {
            $errorMessage = $this->modifyAndLogMessage($e, __FUNCTION__);

            throw new CheckoutApiException($errorMessage);
        }
    }
}
