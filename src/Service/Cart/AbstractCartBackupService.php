<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCartBackupService
{
    public const ORIGIN_CART_TOKEN = 'origin';

    abstract public function getDecorated(): AbstractCartBackupService;

    abstract public function createNewDirectTokenCart(SalesChannelContext $context): Cart;

    abstract public function copyOriginCartToCartContext(SalesChannelContext $context): Cart;

    abstract public function copyDirectCartToCartContext(string $directCartToken, SalesChannelContext $context): Cart;

    abstract public function deleteCart(string $token, SalesChannelContext $context): void;

    abstract public function getBackupCartTokenKey(SalesChannelContext $context): string;

    abstract public function cloneCartAndSave(Cart $sourceCart, string $targetToken, SalesChannelContext $context): Cart;
}
