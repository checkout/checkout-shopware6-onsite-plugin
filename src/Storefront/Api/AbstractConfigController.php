<?php declare(strict_types=1);

namespace Cko\Shopware6\Storefront\Api;

use Cko\Shopware6\Struct\Response\ConfigResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractConfigController
{
    abstract public function getDecorated(): AbstractConfigController;

    abstract public function getPublicConfig(SalesChannelContext $context): ConfigResponse;
}
