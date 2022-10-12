<?php declare(strict_types=1);

namespace Cko\Shopware6\Storefront\Api;

use Cko\Shopware6\Struct\Response\CardPaymentTokenResponse;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCardPaymentController
{
    abstract public function getDecorated(): AbstractCardPaymentController;

    abstract public function createToken(SalesChannelContext $context, RequestDataBag $data): CardPaymentTokenResponse;
}
