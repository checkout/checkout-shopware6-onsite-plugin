<?php declare(strict_types=1);

namespace Cko\Shopware6\Helper;

use Checkout\Common\Address;
use Checkout\Common\Currency;
use Checkout\Common\CustomerRequest;
use Checkout\Payments\ShippingDetails;
use Cko\Shopware6\Struct\DirectPay\Cart\DirectPayCartItemCollection;
use Cko\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use Cko\Shopware6\Struct\LineItemTotalPrice;
use Exception;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Util\FloatComparator;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;

class CheckoutComUtil
{
    /**
     * @throws Exception
     */
    public static function buildReference(OrderEntity $order): string
    {
        $orderNumber = $order->getOrderNumber();
        if ($orderNumber === null) {
            throw new Exception('Order number could not be null');
        }

        return sprintf('ord_%s_id_%s', $orderNumber, $order->getId());
    }

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
     * Build our line item total price struct from the Shopware cart/order
     *
     * @param Cart|OrderEntity $cart
     */
    public static function buildLineItemTotalPrice(Struct $cart): LineItemTotalPrice
    {
        $lineItemTotalPrice = new LineItemTotalPrice();
        $lineItemTotalPrice->setPrice($cart->getPrice());
        $lineItemTotalPrice->setLineItems($cart->getLineItems());
        $lineItemTotalPrice->setDeliveries($cart->getDeliveries());

        return $lineItemTotalPrice;
    }

    /**
     * Build our direct cart struct from the Shopware cart
     */
    public static function buildDirectPayCart(Cart $cart): DirectPayCartStruct
    {
        $directPayCart = new DirectPayCartStruct(
            new DirectPayCartItemCollection(),
            new DirectPayCartItemCollection()
        );

        foreach ($cart->getLineItems() as $item) {
            $itemPrice = $item->getPrice();
            if (!$itemPrice instanceof CalculatedPrice) {
                continue;
            }

            $directPayCart->addLineItem(
                $item->getLabel(),
                $item->getQuantity(),
                $itemPrice->getUnitPrice()
            );
        }

        foreach ($cart->getDeliveries() as $delivery) {
            $grossPrice = $delivery->getShippingCosts()->getUnitPrice();
            if ($grossPrice <= 0) {
                continue;
            }

            $directPayCart->addShipping(
                $delivery->getShippingMethod()->getName(),
                $grossPrice
            );
        }

        $taxAmount = $cart->getPrice()->getCalculatedTaxes()->getAmount();
        if ($taxAmount > 0) {
            $directPayCart->setTax($taxAmount);
        }

        return $directPayCart;
    }

    public static function getCountryStateCode(?CountryStateEntity $countryStateEntity): ?string
    {
        if (!$countryStateEntity instanceof CountryStateEntity) {
            return null;
        }

        $countryStateCode = $countryStateEntity->getShortCode();
        $countryStateData = explode('-', $countryStateCode);
        if (empty($countryStateData)) {
            return null;
        }

        return end($countryStateData);
    }

    /**
     * Format to checkout.com price follow checkout.com documentation
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
            return (int) ceil(FloatComparator::cast($price));
        }

        if (\in_array($currencyCode, self::getValueDividedBy1000Currency(), true)) {
            // We have to multiply by 1000 but the last digit must always be 0
            return (int) ceil(FloatComparator::cast($price * 100)) * 10;
        }

        if (\in_array($currencyCode, self::getValueLast00Currency(), true)) {
            // We have round up first and then multiply by 100 (to get the last 00)
            return (int) ceil(FloatComparator::cast($price)) * 100;
        }

        // We have to multiply by 100
        return (int) ceil(FloatComparator::cast($price * 100));
    }

    /**
     * Format back to shopware price follow checkout.com documentation
     * We have to divide by X because checkout.com will multiply by X when we make the request
     *
     * @see https://www.checkout.com/docs/resources/calculating-the-value
     */
    public static function formatPriceShopware(int $price, string $currencyCode): float
    {
        // We uppercase the currency code for make sure it is valid
        $currencyCode = strtoupper($currencyCode);

        if (\in_array($currencyCode, self::getFullValueCurrency(), true)) {
            // We keep the full value
            return (float) $price;
        }

        if (\in_array($currencyCode, self::getValueDividedBy1000Currency(), true)) {
            return (float) $price / 1000;
        }

        // We have to divide by 100
        return (float) $price / 100;
    }

    public static function floorp(float $val, int $precision = 0): float
    {
        $multiple = pow(10, $precision);

        return floor($val * $multiple) / $multiple;
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
