<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Struct\Response\CardPaymentTokenResponse;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCardPaymentController
{
    abstract public function getDecorated(): AbstractCardPaymentController;

    abstract public function createToken(SalesChannelContext $context, RequestDataBag $data): CardPaymentTokenResponse;
}
