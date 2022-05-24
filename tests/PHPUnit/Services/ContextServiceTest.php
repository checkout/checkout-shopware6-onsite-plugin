<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services;

use CheckoutCom\Shopware6\Service\ContextService;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ContextServiceTest extends TestCase
{
    use ContextTrait;

    /**
     * @var MockObject|SalesChannelContextService
     */
    private $salesChannelContextService;

    private SalesChannelContext $salesChannelContext;

    private ContextService $contextService;

    public function setUp(): void
    {
        $this->salesChannelContextService = $this->createMock(SalesChannelContextService::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->contextService = new ContextService(
            $this->salesChannelContextService
        );
    }

    public function testGetSalesChannelContext(): void
    {
        $this->salesChannelContextService->expects(static::once())
            ->method('get')
            ->willReturn($this->salesChannelContext);

        $salesChannelContext = $this->contextService->getSalesChannelContext('foo', 'bar');

        static::assertInstanceOf(SalesChannelContext::class, $salesChannelContext);
    }
}
