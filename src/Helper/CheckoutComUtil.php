<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Helper;

use Checkout\Common\Address;
use Checkout\Common\Currency;
use Checkout\Common\CustomerRequest;
use Checkout\Payments\ShippingDetails;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;

class CheckoutComUtil
{
    /**
     * Build address request checkout.com from customer address entity shopware
     */
    public static function buildAddress(CustomerAddressEntity $customerAddress): Address
    {
        $address = new Address();
        $address->address_line1 = $customerAddress->getStreet();
        $address->address_line2 = $customerAddress->getAdditionalAddressLine1() ?? $customerAddress->getAdditionalAddressLine2() ?? '';
        $address->city = $customerAddress->getCity();
        $address->state = $customerAddress->getCountryState() !== null ? $customerAddress->getCountryState()->getName() : '';
        $address->zip = $customerAddress->getZipcode();
        $address->country = $customerAddress->getCountry() !== null ? $customerAddress->getCountry()->getIso() : '';

        return $address;
    }

    /**
     * Build ship detail request checkout.com from customer address entity shopware
     */
    public static function buildShipDetail(CustomerAddressEntity $customerAddress): ShippingDetails
    {
        $shippingAddress = CheckoutComUtil::buildAddress($customerAddress);

        $shippingDetails = new ShippingDetails();
        $shippingDetails->address = $shippingAddress;

        return $shippingDetails;
    }

    /**
     * Build customer request checkout.com from customer entity shopware
     */
    public static function buildCustomer(CustomerEntity $customer): CustomerRequest
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
