<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Helper;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * This Util Class is used to get special the request data
 * Only contains the data that are used in the plugin
 */
class RequestUtil
{
    public const DATA_BAG_KEY = 'checkoutComDetails';
    public const DATA_TOKEN = 'token';

    public static function getTokenPayment(RequestDataBag $dataBag): ?string
    {
        $paymentData = static::getPaymentData($dataBag);

        if (!$paymentData instanceof RequestDataBag) {
            return null;
        }

        return $paymentData->get(self::DATA_TOKEN);
    }

    /**
     * Get payment data from a request data bag
     * These data will send from StoreFront or StoreApi
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
}
