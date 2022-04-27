<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Transition\OrderTransactionTransitionService;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Exception\OrderTransactionNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;

class OrderTransactionService extends AbstractOrderTransactionService
{
    private LoggerInterface $logger;

    private EntityRepositoryInterface $orderTransactionRepository;

    private OrderTransactionTransitionService $orderTransactionTransitionService;

    public function __construct(LoggerInterface $logger, EntityRepositoryInterface $orderTransactionRepository, OrderTransactionTransitionService $orderTransactionTransitionService)
    {
        $this->logger = $logger;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->orderTransactionTransitionService = $orderTransactionTransitionService;
    }

    public function getDecorated(): AbstractOrderTransactionService
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Return a transaction order by its ID
     */
    public function getTransaction(string $orderTransactionId, Context $context): OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order');
        $criteria->setLimit(1);

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();
        if (!$orderTransaction instanceof OrderTransactionEntity) {
            $this->logger->critical(
                sprintf('Could not find an order transaction with ID: %s.', $orderTransactionId)
            );

            throw new OrderTransactionNotFoundException($orderTransactionId);
        }

        // If transaction doesn't belong to the order, throw an exception
        if (!$orderTransaction->getOrder() instanceof OrderEntity) {
            $this->logger->critical(
                sprintf('Could not find an order with order transaction ID: %s.', $orderTransactionId)
            );

            throw new EntityNotFoundException('Order of transaction', $orderTransactionId);
        }

        return $orderTransaction;
    }

    /**
     * Process status of order transaction depending on checkout.com payment status
     *
     * @throws Exception
     */
    public function processTransition(OrderTransactionEntity $transaction, ?string $checkoutPaymentStatus, Context $context): void
    {
        switch ($checkoutPaymentStatus) {
            case CheckoutPaymentService::STATUS_DECLINED:
                $this->orderTransactionTransitionService->failTransaction($transaction, $context);

                break;
            case CheckoutPaymentService::STATUS_VOID:
                $this->orderTransactionTransitionService->cancelTransaction($transaction, $context);

                break;
            case CheckoutPaymentService::STATUS_AUTHORIZED:
                $this->orderTransactionTransitionService->authorizeTransaction($transaction, $context);

                break;
            case CheckoutPaymentService::STATUS_PENDING:
                $this->orderTransactionTransitionService->processTransaction($transaction, $context);

                break;
            case CheckoutPaymentService::STATUS_CAPTURED:
                $this->orderTransactionTransitionService->payTransaction($transaction, $context);

                break;
            default:
                $this->logger->critical('Unknown payment status', [
                    'transactionId' => $transaction->getId(),
                    'status' => $checkoutPaymentStatus,
                ]);

                throw new Exception(sprintf('Updating Payment Status of Order not possible for status: %s', $checkoutPaymentStatus));
        }
    }
}
