<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Klarna;

use CheckoutCom\Shopware6\Exception\CheckoutComKlarnaException;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Collection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class KlarnaService
{
    private EntityRepositoryInterface $languageRepository;

    public function __construct(EntityRepositoryInterface $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    public function buildShippingInfo(OrderDeliveryEntity $orderDelivery, ShippingMethodEntity $shippingMethod): array
    {
        $shippingInfo['shipping_company'] = $shippingMethod->getName();
        $shippingInfo['shipping_method'] = $shippingMethod->getName();
        $shippingInfo['tracking_number'] = implode(',', $orderDelivery->getTrackingCodes());
        $shippingInfo['tracking_uri'] = $shippingMethod->getTrackingUrl();

        return $shippingInfo;
    }

    public function getLocaleFromLanguageId(SalesChannelContext $context): string
    {
        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw new CheckoutComKlarnaException('Can\'t get locale. Customer is null');
        }

        $criteria = new Criteria([$customer->getLanguageId()]);
        $criteria->addAssociation('locale');
        $language = $this->languageRepository->search($criteria, $context->getContext())->first();

        if (!$language instanceof LanguageEntity) {
            throw new CheckoutComKlarnaException('Customer locale not found.');
        }

        $locale = $language->getLocale();
        if (!$locale instanceof LocaleEntity) {
            throw new CheckoutComKlarnaException('Customer locale not found.');
        }

        return $locale->getCode();
    }

    public function getPurchaseCountryIsoCodeFromOrder(OrderEntity $order): string
    {
        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress instanceof OrderAddressEntity) {
            throw new CheckoutComKlarnaException('Can\'t get purchase country iso code. Order billing address is null');
        }

        $country = $billingAddress->getCountry();
        if (!$country instanceof CountryEntity) {
            throw new CheckoutComKlarnaException('Can\'t get purchase country iso code. Order country is null');
        }

        return $this->getPurchaseCountryIsoCode($billingAddress);
    }

    public function getPurchaseCountryIsoCodeFromContext(SalesChannelContext $context): string
    {
        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw new CheckoutComKlarnaException('Can\'t get purchase country iso code. Customer is null');
        }

        $billingAddress = $customer->getDefaultBillingAddress();
        if (!$billingAddress instanceof CustomerAddressEntity) {
            throw new CheckoutComKlarnaException('Can\'t get purchase country iso code. Customer billing address is null');
        }

        return $this->getPurchaseCountryIsoCode($billingAddress);
    }

    /**
     * @param OrderLineItemCollection|LineItemCollection|Collection $lineItems
     */
    public function buildProductData(Collection $lineItems, string $currencyIsoCode): array
    {
        $results = [];

        /** @var LineItem|OrderLineItemEntity $lineItem */
        foreach ($lineItems as $lineItem) {
            $price = $lineItem->getPrice();
            if (!$price instanceof CalculatedPrice) {
                continue;
            }

            $calculatedTax = $price->getCalculatedTaxes()->first();
            if (!$calculatedTax) {
                continue;
            }

            $results[] = [
                'name' => $lineItem->getLabel(),
                'quantity' => $lineItem->getQuantity(),
                'unit_price' => CheckoutComUtil::formatPriceCheckout($price->getUnitPrice(), $currencyIsoCode),
                'tax_rate' => (int) $calculatedTax->getTaxRate() * 100,
                'total_amount' => CheckoutComUtil::formatPriceCheckout($price->getTotalPrice(), $currencyIsoCode),
                'total_tax_amount' => CheckoutComUtil::formatPriceCheckout($calculatedTax->getTax(), $currencyIsoCode),
            ];
        }

        return $results;
    }

    /**
     * @param OrderAddressEntity|CustomerAddressEntity $billingAddress
     */
    private function getPurchaseCountryIsoCode($billingAddress): string
    {
        $country = $billingAddress->getCountry();
        if (!$country instanceof CountryEntity) {
            throw new CheckoutComKlarnaException('Can\'t get purchase country iso code. Billing address\'s country is null');
        }

        $isoCode = $country->getIso();
        if (!$isoCode || \strlen($isoCode) !== 2) {
            throw new CheckoutComKlarnaException('Can\'t get purchase country iso code. Invalid iso code is null');
        }

        return $isoCode;
    }
}
