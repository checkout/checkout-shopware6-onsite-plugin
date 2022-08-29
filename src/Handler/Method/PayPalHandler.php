<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\Apm\RequestPayPalSource;
use Checkout\Payments\Product;
use CheckoutCom\Shopware6\Exception\CheckoutComException;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayPalHandler extends PaymentHandler
{
    public function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNames = new DisplayNameTranslationCollection();

        $displayNames->addLangData('en-GB', 'PayPal');

        return $displayNames;
    }

    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$paypal;
    }

    public function prepareDataForPay(
        PaymentRequest $paymentRequest,
        RequestDataBag $dataBag,
        OrderEntity $order,
        SettingStruct $settings,
        SalesChannelContext $context
    ): PaymentRequest {
        $paymentRequest->source = $this->buildPayPalSource($order, $settings);

        if ($settings->isAccountType(SettingStruct::ACCOUNT_TYPE_NAS)) {
            // @phpstan-ignore-next-line
            $paymentRequest->items = $this->buildItems($order);
        }

        return $paymentRequest;
    }

    /**
     * Build request source to call the Checkout.com API
     */
    private function buildPayPalSource(OrderEntity $order, SettingStruct $settings): RequestPayPalSource
    {
        $source = new RequestPayPalSource();

        if ($settings->isAccountType(SettingStruct::ACCOUNT_TYPE_ABC)) {
            $source->invoice_number = CheckoutComUtil::buildReference($order);
        }

        return $source;
    }

    private function buildItems(OrderEntity $order): array
    {
        $currency = $this->orderExtractor->extractCurrency($order);

        $lineItems = $order->getLineItems();
        if (!$lineItems instanceof OrderLineItemCollection) {
            $message = sprintf('The orderLineItems must be instance of OrderLineItemCollection with Order ID: %s', $order->getId());
            $this->logger->error($message);

            throw new CheckoutComException($message);
        }

        $products = [];

        foreach ($lineItems as $lineItem) {
            $product = new Product();
            $product->name = $lineItem->getLabel();
            $product->unit_price = CheckoutComUtil::formatPriceCheckout($lineItem->getUnitPrice(), $currency->getIsoCode());
            $product->quantity = $lineItem->getQuantity();

            $products[] = $product;
        }

        return $products;
    }
}
