<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Storefront\Api;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractApplePayController
{
    abstract public function getDecorated(): AbstractApplePayController;

    abstract public function validateMerchant(SalesChannelContext $context, RequestDataBag $data, Request $request): StoreApiResponse;
}
