<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ContextService
{
    protected SalesChannelContextService $salesChannelContextService;

    public function __construct(SalesChannelContextService $salesChannelContextService)
    {
        $this->salesChannelContextService = $salesChannelContextService;
    }

    public function getSalesChannelContext(string $salesChannelID, string $token): SalesChannelContext
    {
        $params = new SalesChannelContextServiceParameters($salesChannelID, $token);

        return $this->salesChannelContextService->get($params);
    }
}
