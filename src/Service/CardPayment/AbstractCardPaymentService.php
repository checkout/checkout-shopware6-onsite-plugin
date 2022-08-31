<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\CardPayment;

use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\Token;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCardPaymentService
{
    abstract public function getDecorated(): AbstractCardPaymentService;

    abstract public function createToken(RequestDataBag $data, SalesChannelContext $context): Token;
}
