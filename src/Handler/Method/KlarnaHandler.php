<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\Country;
use Checkout\Common\PaymentSourceType;
use Checkout\Payments\PaymentRequest;
use Checkout\Payments\Source\Apm\RequestKlarnaSource;
use CheckoutCom\Shopware6\Exception\CheckoutInvalidTokenException;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Helper\RequestUtil;
use CheckoutCom\Shopware6\Service\Klarna\KlarnaService;
use CheckoutCom\Shopware6\Service\Order\AbstractOrderService;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class KlarnaHandler extends PaymentHandler
{
    private KlarnaService $klarnaService;

    private AbstractOrderService $orderService;

    public function setCustomServices(
        KlarnaService $klarnaService,
        AbstractOrderService $orderService
    ): void {
        $this->klarnaService = $klarnaService;
        $this->orderService = $orderService;
    }

    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'Klarna');

        return $displayNames;
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$klarna;
    }

    public function getAvailableCountries(): array
    {
        return [
            Country::$DE,
            Country::$SE,
            Country::$GB,
            Country::$ES,
            Country::$NO,
            Country::$FR,
        ];
    }

    public function canManualCapture(SalesChannelContext $context): bool
    {
        return true;
    }

    public function capturePayment(string $checkoutPaymentId, OrderEntity $order): string
    {
        return $this->klarnaService->capturePayment($checkoutPaymentId, $order);
    }

    public function voidPayment(string $checkoutPaymentId, OrderEntity $order): string
    {
        return $this->klarnaService->voidPayment($checkoutPaymentId, $order);
    }

    public function shouldCaptureAfterShipping(): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildKlarnaSource($order, $dataBag, $context);

        return $paymentRequest;
    }

    /**
     * Build request source to call the Checkout.com API
     */
    private function buildKlarnaSource(
        OrderEntity $order,
        RequestDataBag $data,
        SalesChannelContext $context
    ): RequestKlarnaSource {
        $token = RequestUtil::getTokenPayment($data);
        if (!\is_string($token)) {
            throw new CheckoutInvalidTokenException(static::getPaymentMethodType());
        }

        $orderCustomer = $this->orderExtractor->extractCustomer($order);
        $currency = $this->orderExtractor->extractCurrency($order);
        $billingAddress = $this->orderExtractor->extractBillingAddress($order, $context);
        $currencyIso = $currency->getIsoCode();

        /** @var Country $purchaseCountry */
        $purchaseCountry = $this->countryService->getPurchaseCountryIsoCodeFromOrder($order);

        $source = new RequestKlarnaSource();
        $source->authorization_token = $token;
        $source->locale = $this->contextService->getLocaleCode($context);
        $source->purchase_country = $purchaseCountry;
        $source->tax_amount = CheckoutComUtil::formatPriceCheckout(
            $order->getPrice()->getCalculatedTaxes()->getAmount(),
            $currencyIso
        );
        $source->billing_address = $this->buildShippingAddress($orderCustomer, $billingAddress);
        $source->products = $this->buildKlarnaProducts(
            $order->getId(),
            $currencyIso,
            $context
        );

        return $source;
    }

    private function buildKlarnaProducts(string $orderId, string $currencyIso, SalesChannelContext $context): array
    {
        $order = $this->orderService->getOrder(
            $context->getContext(),
            $orderId,
            [
                'lineItems',
                'deliveries.shippingMethod',
            ]
        );

        return $this->klarnaService->buildProductData(
            CheckoutComUtil::buildLineItemTotalPrice($order),
            $currencyIso
        );
    }

    /**
     * Build shipping address
     */
    private function buildShippingAddress(
        OrderCustomerEntity $orderCustomer,
        OrderAddressEntity $addressEntity
    ): array {
        $countryState = $addressEntity->getCountryState();
        $region = null;
        if ($countryState instanceof CountryStateEntity) {
            $region = $this->countryService->getCountryStateCode($countryState);
        }

        return [
            'city' => $addressEntity->getCity(),
            'country' => $addressEntity->getCountry() !== null ? $addressEntity->getCountry()->getIso() : null,
            'email' => $orderCustomer->getEmail(),
            'family_name' => $orderCustomer->getLastName(),
            'given_name' => $orderCustomer->getFirstName(),
            'postal_code' => $addressEntity->getZipcode(),
            'region' => $region,
            'street_address' => $addressEntity->getStreet(),
            'street_address2' => $addressEntity->getAdditionalAddressLine1(),
        ];
    }
}
