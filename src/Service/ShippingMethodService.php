<?php declare(strict_types=1);

namespace Cko\Shopware6\Service;

use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ShippingMethodService
{
    private EntityRepositoryInterface $shippingMethodRepository;

    public function __construct(EntityRepositoryInterface $shippingMethodRepository)
    {
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    public function getActiveShippingMethods(SalesChannelContext $salesChannelContext): ShippingMethodCollection
    {
        $criteria = new Criteria();

        $criteria
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsFilter('salesChannels.id', $salesChannelContext->getSalesChannel()->getId()))
            ->addFilter(new EqualsAnyFilter('availabilityRuleId', $salesChannelContext->getRuleIds()))
            ->addAssociation('prices')
            ->addAssociation('salesChannels');

        /** @var ShippingMethodCollection $shippingMethods */
        $shippingMethods = $this->shippingMethodRepository
            ->search($criteria, $salesChannelContext->getContext())
            ->getEntities();

        return $shippingMethods->filterByActiveRules($salesChannelContext);
    }
}
