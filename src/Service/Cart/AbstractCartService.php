<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCartService
{
    abstract public function getDecorated(): AbstractCartBackupService;

    abstract public function addProductToCart(string $productId, int $quantity, Cart $directCart, SalesChannelContext $context): Cart;

    abstract public function getCart(string $token, SalesChannelContext $context): Cart;
}
