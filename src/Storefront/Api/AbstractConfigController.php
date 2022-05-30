<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Struct\Response\ConfigResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractConfigController
{
    abstract public function getDecorated(): AbstractConfigController;

    abstract public function getPublicConfig(SalesChannelContext $context): ConfigResponse;
}
