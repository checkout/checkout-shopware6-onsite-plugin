<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use CheckoutCom\Shopware6\Struct\Response\AddProductToDirectCartResponse;
use CheckoutCom\Shopware6\Struct\Response\DirectProcessResponse;
use CheckoutCom\Shopware6\Struct\Response\DirectShippingResponse;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SuccessResponse;

abstract class AbstractDirectPayController
{
    abstract public function getDecorated(): AbstractDirectPayController;

    abstract public function addProductToDirectCart(SalesChannelContext $context, RequestDataBag $data): AddProductToDirectCartResponse;

    abstract public function removeBackUp(SalesChannelContext $context, RequestDataBag $data): SuccessResponse;

    abstract public function getShippingMethods(SalesChannelContext $context, RequestDataBag $data): DirectShippingResponse;

    abstract public function updateShippingPayload(SalesChannelContext $context, RequestDataBag $data): DirectShippingResponse;

    abstract public function processPayment(SalesChannelContext $context, RequestDataBag $data): DirectProcessResponse;
}
