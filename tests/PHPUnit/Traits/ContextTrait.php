<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

trait ContextTrait
{
    /**
     * @return MockObject|SalesChannelContext
     */
    protected function getSaleChannelContext(TestCase $testCase)
    {
        $context = $this->getContext($testCase);

        $salesChannel = $testCase->createConfiguredMock(SalesChannelEntity::class, [
            'getId' => 'bar',
        ]);

        return $testCase->createConfiguredMock(SalesChannelContext::class, [
            'getSalesChannel' => $salesChannel,
            'getContext' => $context,
        ]);
    }

    /**
     * @return MockObject|Context
     */
    protected function getContext(TestCase $testCase)
    {
        return $testCase->createMock(Context::class);
    }
}
