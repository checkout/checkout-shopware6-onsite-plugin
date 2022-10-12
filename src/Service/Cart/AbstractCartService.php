<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCartService
{
    abstract public function getDecorated(): AbstractCartBackupService;

    abstract public function addProductToCart(string $productId, int $quantity, Cart $directCart, SalesChannelContext $context): Cart;

    abstract public function recalculateByCart(Cart $cart, SalesChannelContext $context): Cart;

    abstract public function recalculateCart(SalesChannelContext $context): Cart;

    abstract public function getCart(string $token, SalesChannelContext $context): Cart;

    abstract public function getShippingCostsPrice(Cart $cart): float;

    abstract public function updateContextCountry(SalesChannelContext $context, string $countryID): SalesChannelContext;

    abstract public function updateContextShippingMethod(SalesChannelContext $context, string $shippingMethodID): SalesChannelContext;

    abstract public function updateContextPaymentMethod(SalesChannelContext $context, string $paymentMethodId): SalesChannelContext;
}
