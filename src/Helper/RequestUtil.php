<?php declare(strict_types=1);

namespace Cko\Shopware6\Helper;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * Get data from payment request with checkoutComDetails key
 */
class RequestUtil
{
    public const DATA_BAG_KEY = 'checkoutComDetails';
    public const DATA_JSON = 'json';
    public const DATA_TOKEN = 'token';
    public const DATA_SOURCE = 'source';
    public const DATA_SOURCE_ID = 'sourceId';
    public const DATA_SHOULD_SAVE_SOURCE = 'shouldSaveSource';

    /**
     * @return string|RequestDataBag|null
     */
    public static function getTokenPayment(RequestDataBag $dataBag)
    {
        $paymentData = static::getPaymentData($dataBag);
        if (!$paymentData instanceof RequestDataBag) {
            return null;
        }

        return $paymentData->get(self::DATA_TOKEN);
    }

    public static function getSourceIdPayment(RequestDataBag $dataBag): ?string
    {
        $paymentData = static::getPaymentData($dataBag);
        if (!$paymentData instanceof RequestDataBag) {
            return null;
        }

        return $paymentData->get(self::DATA_SOURCE_ID);
    }

    public static function getShouldSaveSource(RequestDataBag $dataBag): bool
    {
        $paymentData = static::getPaymentData($dataBag);
        if (!$paymentData instanceof RequestDataBag) {
            return false;
        }

        $shouldSaveSource = $paymentData->get(self::DATA_SHOULD_SAVE_SOURCE);

        return $shouldSaveSource === true || $shouldSaveSource === 'true';
    }

    /**
     * Get payment data from a request data bag
     * This data is our plugin's payment request data
     * These data is coming from StoreFront or StoreApi
     */
    public static function getPaymentData(RequestDataBag $dataBag): ?RequestDataBag
    {
        $dataBagKey = self::DATA_BAG_KEY;
        if (!$dataBag->has($dataBagKey)) {
            return null;
        }

        $paymentDetails = $dataBag->get($dataBagKey);
        if (!$paymentDetails instanceof RequestDataBag) {
            return null;
        }

        return $paymentDetails;
    }

    /**
     * @return string|RequestDataBag|null
     */
    public static function getSource(RequestDataBag $dataBag)
    {
        $paymentData = static::getPaymentData($dataBag);
        if (!$paymentData instanceof RequestDataBag) {
            return null;
        }

        return $paymentData->get(self::DATA_SOURCE);
    }
}
