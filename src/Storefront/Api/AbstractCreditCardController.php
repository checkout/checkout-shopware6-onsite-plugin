<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Struct\Response\CreditCardTokenResponse;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCreditCardController
{
    abstract public function getDecorated(): AbstractCreditCardController;

    abstract public function createToken(SalesChannelContext $context, RequestDataBag $data): CreditCardTokenResponse;
}
