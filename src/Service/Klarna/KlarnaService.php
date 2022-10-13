<?php
declare(strict_types=1);

namespace Cko\Shopware6\Service\Klarna;

use Checkout\Apm\Previous\Klarna\CreditSessionRequest;
use Checkout\Apm\Previous\Klarna\Klarna;
use Checkout\Apm\Previous\Klarna\OrderCaptureRequest;
use Checkout\CheckoutApiException;
use Checkout\Payments\Previous\Source\Apm\KlarnaProduct;
use Checkout\Payments\VoidRequest;
use Cko\Shopware6\Helper\CheckoutComUtil;
use Cko\Shopware6\Service\CheckoutApi\Apm\CheckoutKlarnaService;
use Cko\Shopware6\Service\ContextService;
use Cko\Shopware6\Service\CountryService;
use Cko\Shopware6\Service\Extractor\AbstractOrderExtractor;
use Cko\Shopware6\Struct\LineItemTotalPrice;
use Cko\Shopware6\Struct\PaymentMethod\Klarna\CreditSessionStruct;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Struct\Collection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class KlarnaService
{
    private ContextService $contextService;

    private CountryService $countryService;

    private CheckoutKlarnaService $checkoutKlarnaService;

    private AbstractOrderExtractor $orderExtractor;

    public function __construct(
        ContextService $contextService,
        CountryService $countryService,
        CheckoutKlarnaService $checkoutKlarnaService,
        AbstractOrderExtractor $orderExtractor
    ) {
        $this->contextService = $contextService;
        $this->countryService = $countryService;
        $this->checkoutKlarnaService = $checkoutKlarnaService;
        $this->orderExtractor = $orderExtractor;
    }

    /**
     * @throws CheckoutApiException
     */
    public function createCreditSession(LineItemTotalPrice $lineItemTotalPrice, SalesChannelContext $context): CreditSessionStruct
    {
        return $this->checkoutKlarnaService->createCreditSession(
            $this->buildCreditSessionRequest($lineItemTotalPrice, $context),
            $context->getSalesChannelId()
        );
    }

    /**
     * @throws CheckoutApiException
     */
    public function capturePayment(string $paymentId, OrderEntity $order): string
    {
        return $this->checkoutKlarnaService->capturePayment(
            $paymentId,
            $this->buildCapturePaymentRequest($order),
            $order->getSalesChannelId()
        );
    }

    /**
     * @throws CheckoutApiException
     */
    public function voidPayment(string $paymentId, OrderEntity $order): string
    {
        return $this->checkoutKlarnaService->voidPayment(
            $paymentId,
            $this->buildVoidPaymentRequest($order),
            $order->getSalesChannelId()
        );
    }

    public function buildShippingInfo(OrderDeliveryEntity $orderDelivery, ShippingMethodEntity $shippingMethod): array
    {
        return [
            [
                'shipping_company' => $shippingMethod->getName() ?? '',
                'tracking_number' => implode(',', $orderDelivery->getTrackingCodes()),
                'tracking_uri' => $shippingMethod->getTrackingUrl() ?? '',
            ],
        ];
    }

    public function buildProductData(LineItemTotalPrice $lineItemTotalPrice, string $currencyIsoCode): array
    {
        return array_merge(
            $this->buildProductLineItem($lineItemTotalPrice->getLineItems(), $currencyIsoCode),
            $this->buildProductShippingItem($lineItemTotalPrice->getDeliveries(), $currencyIsoCode)
        );
    }

    /**
     * @param OrderLineItemCollection|LineItemCollection|null $lineItems
     */
    private function buildProductLineItem(?Collection $lineItems, string $currencyIsoCode): array
    {
        $results = [];
        if (empty($lineItems) || $lineItems->count() === 0) {
            return $results;
        }

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

            $product = new KlarnaProduct();
            $product->name = $lineItem->getLabel() ?? '';
            $product->quantity = $lineItem->getQuantity();
            $product->unit_price = CheckoutComUtil::formatPriceCheckout($price->getUnitPrice(), $currencyIsoCode);
            $product->tax_rate = (int) $calculatedTax->getTaxRate() * 100;
            $product->total_amount = CheckoutComUtil::formatPriceCheckout($price->getTotalPrice(), $currencyIsoCode);
            $product->total_tax_amount = CheckoutComUtil::formatPriceCheckout($calculatedTax->getTax(), $currencyIsoCode);

            $results[] = $product;
        }

        return $results;
    }

    /**
     * @param DeliveryCollection|OrderDeliveryCollection|null $deliveries
     */
    private function buildProductShippingItem(?Collection $deliveries, string $currencyIsoCode): array
    {
        $results = [];
        if (empty($deliveries) || $deliveries->count() === 0) {
            return $results;
        }

        /** @var Delivery $delivery */
        foreach ($deliveries as $delivery) {
            $shippingCosts = $delivery->getShippingCosts();
            $grossPrice = $shippingCosts->getUnitPrice();
            if ($grossPrice <= 0) {
                continue;
            }

            $calculatedTax = $shippingCosts->getCalculatedTaxes()->first();
            if (!$calculatedTax) {
                continue;
            }

            $product = new KlarnaProduct();
            $product->name = $delivery->getShippingMethod()->getName() ?? '';
            $product->quantity = $shippingCosts->getQuantity();
            $product->unit_price = CheckoutComUtil::formatPriceCheckout($grossPrice, $currencyIsoCode);
            $product->tax_rate = (int) $calculatedTax->getTaxRate() * 100;
            $product->total_amount = CheckoutComUtil::formatPriceCheckout($grossPrice, $currencyIsoCode);
            $product->total_tax_amount = CheckoutComUtil::formatPriceCheckout($calculatedTax->getTax(), $currencyIsoCode);

            $results[] = $product;
        }

        return $results;
    }

    /**
     * Build request data to create the credit session
     */
    private function buildCreditSessionRequest(LineItemTotalPrice $lineItemTotalPrice, SalesChannelContext $context): CreditSessionRequest
    {
        $currency = $context->getCurrency();

        $cartPrice = $lineItemTotalPrice->getPrice();

        $request = new CreditSessionRequest();
        $request->purchase_country = $this->countryService->getPurchaseCountryIsoCodeFromContext($context);
        $request->currency = $currency->getIsoCode();
        $request->locale = $this->contextService->getLocaleCode($context);
        $request->amount = CheckoutComUtil::formatPriceCheckout($cartPrice->getTotalPrice(), $currency->getIsoCode());
        $request->tax_amount = CheckoutComUtil::formatPriceCheckout(
            $cartPrice->getCalculatedTaxes()->getAmount(),
            $currency->getIsoCode()
        );
        $request->products = $this->buildProductData($lineItemTotalPrice, $currency->getIsoCode());

        return $request;
    }

    /**
     * Build request data to capture the payment
     */
    private function buildCapturePaymentRequest(OrderEntity $order): OrderCaptureRequest
    {
        $currency = $this->orderExtractor->extractCurrency($order);

        $klarna = new Klarna();
        $klarna->description = CheckoutComUtil::buildReference($order);
        $klarna->products = $this->buildProductData(
            CheckoutComUtil::buildLineItemTotalPrice($order),
            $currency->getIsoCode()
        );
        $klarna->shipping_info = $this->buildShippingInfo(
            $this->orderExtractor->extractOrderDelivery($order),
            $this->orderExtractor->extractOrderShippingMethod($order)
        );
        $klarna->shipping_delay = 0;

        $request = new OrderCaptureRequest();
        $request->amount = CheckoutComUtil::formatPriceCheckout($order->getAmountTotal(), $currency->getIsoCode());
        $request->reference = (int) $this->orderExtractor->extractOrderNumber($order);
        $request->klarna = $klarna;

        return $request;
    }

    /**
     * Build request data to void the payment
     */
    private function buildVoidPaymentRequest(OrderEntity $order): VoidRequest
    {
        $request = new VoidRequest();
        $request->reference = $this->orderExtractor->extractOrderNumber($order);

        return $request;
    }
}
