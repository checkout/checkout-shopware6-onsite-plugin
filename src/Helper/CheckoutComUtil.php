<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Helper;

use Checkout\Common\Address;
use Checkout\Common\Currency;
use Checkout\Common\CustomerRequest;
use Checkout\Payments\ShippingDetails;
use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;

class CheckoutComUtil
{
    /**
     * Build ship detail request checkout.com from order address entity shopware
     */
    public static function buildShipDetail(OrderAddressEntity $orderShippingAddress): ShippingDetails
    {
        $shippingAddress = CheckoutComUtil::buildAddress($orderShippingAddress);

        $shippingDetails = new ShippingDetails();
        $shippingDetails->address = $shippingAddress;

        return $shippingDetails;
    }

    /**
     * Build address request checkout.com from customer address entity shopware
     *
     * @throws Exception
     */
    public static function buildAddress(OrderAddressEntity $addressEntity): Address
    {
        $address = new Address();
        $address->address_line1 = $addressEntity->getStreet();
        $address->address_line2 = $addressEntity->getAdditionalAddressLine1() ?? $addressEntity->getAdditionalAddressLine2() ?? '';
        $address->city = $addressEntity->getCity();
        $address->state = $addressEntity->getCountryState() !== null ? ($addressEntity->getCountryState()->getName() ?? '') : '';
        $address->zip = $addressEntity->getZipcode();
        $address->country = $addressEntity->getCountry() !== null ? ($addressEntity->getCountry()->getIso() ?? '') : '';

        return $address;
    }

    /**
     * Build customer request checkout.com from customer entity shopware
     */
    public static function buildCustomer(OrderCustomerEntity $customer): CustomerRequest
    {
        $customerRequest = new CustomerRequest();
        $customerRequest->name = sprintf('%s %s', $customer->getFirstName(), $customer->getLastName());
        $customerRequest->email = $customer->getEmail();

        return $customerRequest;
    }

    /**
     * Format price follow checkout.com documentation
     * We have to multiply by X because checkout.com will divide by X when we make the request
     *
     * @see https://www.checkout.com/docs/resources/calculating-the-value
     */
    public static function formatPriceCheckout(float $price, string $currencyCode): int
    {
        // We uppercase the currency code for make sure it is valid
        $currencyCode = strtoupper($currencyCode);

        if (\in_array($currencyCode, self::getFullValueCurrency(), true)) {
            // We keep the full value
            return (int) ceil($price);
        }

        if (\in_array($currencyCode, self::getValueDividedBy1000Currency(), true)) {
            // We have to multiply by 1000 but the last digit must always be 0
            return (int) ceil($price * 100) * 10;
        }

        if (\in_array($currencyCode, self::getValueLast00Currency(), true)) {
            // We have round up first and then multiply by 100 (to get the last 00)
            return (int) ceil($price) * 100;
        }

        // We have to multiply by 100
        return (int) ceil($price * 100);
    }

    /**
     * @see https://www.checkout.com/docs/resources/calculating-the-value#Option_1:_The_full_value
     */
    private static function getFullValueCurrency(): array
    {
        return [
            Currency::$BIF,
            Currency::$CLF,
            Currency::$DJF,
            Currency::$GNF,
            Currency::$ISK,
            Currency::$JPY,
            Currency::$KMF,
            Currency::$KRW,
            Currency::$PYG,
            Currency::$RWF,
            Currency::$UGX,
            Currency::$VUV,
            Currency::$VND,
            Currency::$XAF,
            Currency::$XOF,
            Currency::$XPF,
        ];
    }

    /**
     * @see https://www.checkout.com/docs/resources/calculating-the-value#Option_2:_The_value_divided_by_1000
     */
    private static function getValueDividedBy1000Currency(): array
    {
        return [
            Currency::$BHD,
            Currency::$IQD,
            Currency::$JOD,
            Currency::$KWD,
            Currency::$LYD,
            Currency::$OMR,
            Currency::$TND,
        ];
    }

    /**
     * @see https://www.checkout.com/docs/resources/calculating-the-value#Option_3:_The_value_divided_by_100
     */
    private static function getValueLast00Currency(): array
    {
        return [
            Currency::$CLP,
        ];
    }
}
