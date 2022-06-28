<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\CreditCard;

use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Token;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCreditCardService
{
    abstract public function getDecorated(): AbstractCreditCardService;

    abstract public function createToken(RequestDataBag $data, SalesChannelContext $context): Token;
}
