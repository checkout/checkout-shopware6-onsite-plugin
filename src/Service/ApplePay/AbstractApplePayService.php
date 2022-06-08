<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\ApplePay;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractApplePayService
{
    abstract public function getDecorated(): AbstractApplePayService;

    abstract public function validateMerchant(string $appleValidateUrl, Request $request, SalesChannelContext $context): ?array;

    abstract public function getAppleKeyMedia(SalesChannelContext $context): MediaEntity;

    abstract public function getApplePemMedia(SalesChannelContext $context): MediaEntity;

    abstract public function getAppleDomainMedia(SalesChannelDomainEntity $salesChannelDomain, SalesChannelContext $context): MediaEntity;
}
