<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Previous\PaymentRequest;
use Checkout\Payments\Previous\Source\Apm\RequestPayPalSource;
use Checkout\Payments\Product;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
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

        $products = [];
        $product = new Product();
        $product->quantity = 1;
        $product->name = $this->translator->trans('checkoutCom.payments.totalPriceLabel');
        if ($order->getTaxStatus() === CartPrice::TAX_STATE_FREE) {
            $product->unit_price = CheckoutComUtil::formatPriceCheckout($order->getAmountNet(), $currency->getIsoCode());
        } else {
            $product->unit_price = CheckoutComUtil::formatPriceCheckout($order->getAmountTotal(), $currency->getIsoCode());
        }

        $products[] = $product;

        return $products;
    }
}
