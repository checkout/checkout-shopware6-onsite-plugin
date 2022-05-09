<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ContextService
{
    private LoggerInterface $logger;

    private SalesChannelContextService $salesChannelContextService;

    public function __construct(LoggerInterface $logger, SalesChannelContextService $salesChannelContextService)
    {
        $this->logger = $logger;
        $this->salesChannelContextService = $salesChannelContextService;
    }

    public function getSalesChannelContext(string $salesChannelID, string $token): SalesChannelContext
    {
        $params = new SalesChannelContextServiceParameters($salesChannelID, $token);

        return $this->salesChannelContextService->get($params);
    }

    public function getSalesChannelDomain(?string $domainId, SalesChannelContext $context): SalesChannelDomainEntity
    {
        if ($domainId === null) {
            $message = 'Domain ID is null';
            $this->logger->critical($message);

            throw new CheckoutComException($message);
        }

        $salesChannel = $context->getSalesChannel();
        $domains = $salesChannel->getDomains();
        if (!$domains instanceof SalesChannelDomainCollection) {
            $this->logger->critical('Not found domains for sales channel');

            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        $domain = $domains->get($domainId);
        if (!$domain instanceof SalesChannelDomainEntity) {
            $this->logger->critical(sprintf('Not found domain for domain ID: %s', $domainId));

            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        return $domain;
    }
}
