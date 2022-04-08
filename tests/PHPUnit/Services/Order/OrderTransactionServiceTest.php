<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Order;

use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\LoggerService;
use CheckoutCom\Shopware6\Service\Order\OrderTransactionService;
use CheckoutCom\Shopware6\Service\Transition\OrderTransactionTransitionService;
use CheckoutCom\Shopware6\Tests\Fakes\FakeEntityRepository;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Exception\OrderTransactionNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderTransactionServiceTest extends TestCase
{
    use ContextTrait;
    use OrderTrait;

    private FakeEntityRepository $orderTransactionRepository;

    /**
     * @var MockObject|OrderTransactionTransitionService
     */
    private $orderTransactionTransitionService;

    private OrderTransactionService $orderTransactionService;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->orderTransactionRepository = new FakeEntityRepository(new OrderTransactionDefinition());
        $this->orderTransactionTransitionService = $this->createMock(OrderTransactionTransitionService::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->orderTransactionService = new OrderTransactionService(
            $this->createMock(LoggerService::class),
            $this->orderTransactionRepository,
            $this->orderTransactionTransitionService
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->orderTransactionService->getDecorated();
    }

    /**
     * @dataProvider transactionProvider
     */
    public function testGetTransaction(bool $hasOrderTransaction, bool $hasOrder): void
    {
        $orderTransaction = null;
        $orderTransactionId = 'foo';
        if ($hasOrderTransaction) {
            $orderTransaction = $this->getOrderTransaction();
            $orderTransaction->setId($orderTransactionId);
            if ($hasOrder) {
                $orderTransaction->setOrder($this->getOrder());
            } else {
                static::expectException(EntityNotFoundException::class);
            }
        } else {
            static::expectException(OrderTransactionNotFoundException::class);
        }

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $orderTransaction,
        ]);

        $this->orderTransactionRepository->entitySearchResults[] = $search;
        $actualTransaction = $this->orderTransactionService->getTransaction($orderTransactionId, $this->salesChannelContext->getContext());

        static::assertSame($orderTransaction, $actualTransaction);
    }

    /**
     * @dataProvider processTransitionProvider
     */
    public function testProcessTransition(bool $expectThrowException, string $checkoutPaymentStatus, ?string $expectFunction = null): void
    {
        if ($expectThrowException) {
            static::expectException(Exception::class);
        }

        if ($expectFunction) {
            $this->orderTransactionTransitionService->expects(static::exactly(1))
                ->method($expectFunction);
        }

        $this->orderTransactionService->processTransition($this->getOrderTransaction(), $checkoutPaymentStatus, $this->salesChannelContext->getContext());
    }

    public function transactionProvider(): array
    {
        return [
            'Test could not find order transaction' => [
                false,
                false,
            ],
            'Test found order transaction but could not find order' => [
                true,
                false,
            ],
            'Test found order transaction and order' => [
                true,
                true,
            ],
        ];
    }

    public function processTransitionProvider(): array
    {
        return [
            'Test not found checkout status must throw exception' => [
                true,
                'Do not exists checkout status',
            ],
            'Test transition order transaction success with checkout status is declined' => [
                false,
                CheckoutPaymentService::STATUS_DECLINED,
                'failTransaction',
            ],
            'Test transition order transaction success with checkout status is voided' => [
                false,
                CheckoutPaymentService::STATUS_VOID,
                'cancelTransaction',
            ],
            'Test transition order transaction success with checkout status is authorized' => [
                false,
                CheckoutPaymentService::STATUS_AUTHORIZED,
                'authorizeTransaction',
            ],
            'Test transition order transaction success with checkout status is pending' => [
                false,
                CheckoutPaymentService::STATUS_PENDING,
                'processTransaction',
            ],
            'Test transition order transaction success with checkout status is captured' => [
                false,
                CheckoutPaymentService::STATUS_CAPTURED,
                'payTransaction',
            ],
        ];
    }
}
