<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\CardPayment;

use Cko\Shopware6\Struct\CheckoutApi\Resources\Token;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCardPaymentService
{
    abstract public function getDecorated(): AbstractCardPaymentService;

    abstract public function createToken(RequestDataBag $data, SalesChannelContext $context): Token;
}
