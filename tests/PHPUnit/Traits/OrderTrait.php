<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Traits;

use Cko\Shopware6\Service\Order\OrderService;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\Salutation\SalutationEntity;

trait OrderTrait
{
    public function getCustomerAddressEntity(
        string $firstName,
        string $lastName,
        string $street,
        string $city,
        string $zip,
        ?string $addressLine1 = null,
        ?string $addressLine2 = null,
        ?string $stateName = null,
        ?string $countryISO = null,
        ?string $salutationName = null
    ): OrderAddressEntity {
        $customerAddress = new OrderAddressEntity();
        $customerAddress->setId(Uuid::randomHex());

        if (!empty($salutationName)) {
            $salutation = new SalutationEntity();
            $salutation->setId(Uuid::randomHex());
            $salutation->setDisplayName($salutationName);
            $customerAddress->setSalutation($salutation);
        }

        $customerAddress->setFirstName($firstName);
        $customerAddress->setLastName($lastName);
        $customerAddress->setStreet($street);
        $customerAddress->setCity($city);
        $customerAddress->setZipcode($zip);

        if (!empty($addressLine1)) {
            $customerAddress->setAdditionalAddressLine1($addressLine1);
        }
        if (!empty($addressLine2)) {
            $customerAddress->setAdditionalAddressLine2($addressLine2);
        }

        if (!empty($countryISO)) {
            $country = new CountryEntity();
            $country->setId(Uuid::randomHex());
            $country->setIso($countryISO);
            $customerAddress->setCountry($country);
        }

        if (!empty($stateName)) {
            $countryState = new CountryStateEntity();
            $countryState->setId(Uuid::randomHex());
            $countryState->setName($stateName);
            $customerAddress->setCountryState($countryState);
        }

        return $customerAddress;
    }

    public function getOrderCustomerEntity(
        string $firstName,
        string $lastName,
        string $email
    ): OrderCustomerEntity {
        $customer = new OrderCustomerEntity();
        $customer->setId(Uuid::randomHex());

        $customer->setFirstName($firstName);
        $customer->setLastName($lastName);
        $customer->setEmail($email);

        return $customer;
    }

    public function getLanguage(string $localeCode): LanguageEntity
    {
        $locale = new LocaleEntity();
        $locale->setId(Uuid::randomHex());
        $locale->setCode($localeCode);
        $language = new LanguageEntity();
        $language->setId(Uuid::randomHex());
        $language->setLocale($locale);

        return $language;
    }

    public function getOrder(?string $checkoutPaymentId = null): OrderEntity
    {
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setOrderNumber(Uuid::randomHex());
        $order->setSalesChannelId(Uuid::randomHex());
        $order->setTaxStatus(CartPrice::TAX_STATE_FREE);
        $order->setAmountNet(0);
        $order->setAmountTotal(0);

        $checkoutOrderCustomFields = OrderService::getCheckoutOrderCustomFields($order);
        $checkoutOrderCustomFields->setCheckoutPaymentId($checkoutPaymentId);
        $order->setCustomFields([
            OrderService::CHECKOUT_CUSTOM_FIELDS => $checkoutOrderCustomFields->jsonSerialize(),
        ]);

        return $order;
    }

    public function getCurrency(?string $isoCode = 'EUR'): CurrencyEntity
    {
        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode($isoCode);

        return $currency;
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());

        return $orderTransaction;
    }
}
