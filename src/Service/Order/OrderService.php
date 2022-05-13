<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Transition\OrderTransitionService;
use CheckoutCom\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderService extends AbstractOrderService
{
    public const CHECKOUT_CUSTOM_FIELDS = 'checkoutComPayments';

    /**
     * This property tells us know the last order id that was increased to the DB
     * It will be set whenever the order is created
     * Do not change class to a Non-Shared class, otherwise it won't work
     *
     * @see \CheckoutCom\Shopware6\Subscriber\CheckoutOrderPlacedEventSubscriber::onCheckoutOrderPlaced
     */
    private ?string $requestLastOrderId = null;

    private LoggerInterface $logger;

    private EntityRepositoryInterface $orderRepository;

    private OrderTransitionService $orderTransitionService;

    public function __construct(LoggerInterface $logger, EntityRepositoryInterface $orderRepository, OrderTransitionService $orderTransitionService)
    {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderTransitionService = $orderTransitionService;
    }

    public function getDecorated(): AbstractOrderService
    {
        throw new DecorationPatternException(self::class);
    }

    public function setRequestLastOrderId(string $lastOrderId): void
    {
        $this->requestLastOrderId = $lastOrderId;
    }

    public function getRequestLastOrderId(): ?string
    {
        return $this->requestLastOrderId;
    }

    public function getOrder(string $orderId, Context $context): OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->setLimit(1);

        $order = $this->orderRepository->search($criteria, $context)->first();
        if (!$order instanceof OrderEntity) {
            $this->logger->critical(
                sprintf('Could not fetch order with ID %s', $orderId)
            );

            throw new OrderNotFoundException($orderId);
        }

        return $order;
    }

    /**
     * Update order custom fields of the order for checkout.com payments
     */
    public function updateCheckoutCustomFields(OrderEntity $order, OrderCustomFieldsStruct $orderCustomFields, SalesChannelContext $context): void
    {
        $this->logger->debug('Updating order checkout custom fields', [
            'customerId' => $order->getId(),
            'customFields' => [
                self::CHECKOUT_CUSTOM_FIELDS => $orderCustomFields->jsonSerialize(),
            ],
        ]);

        $this->orderRepository->update([
            [
                'id' => $order->getId(),
                'customFields' => [
                    self::CHECKOUT_CUSTOM_FIELDS => $orderCustomFields->jsonSerialize(),
                ],
            ],
        ], $context->getContext());
    }

    /**
     * Get custom fields of the order for checkout.com
     */
    public static function getCheckoutOrderCustomFields(OrderEntity $order): OrderCustomFieldsStruct
    {
        $customFields = $order->getCustomFields() ?? [];

        $checkoutOrderCustomFields = new OrderCustomFieldsStruct();
        $checkoutOrderCustomFields->assign($customFields[self::CHECKOUT_CUSTOM_FIELDS] ?? []);

        return $checkoutOrderCustomFields;
    }

    /**
     * Process status of order depending on checkout.com payment status
     *
     * @throws Exception
     */
    public function processTransition(OrderEntity $order, SettingStruct $settings, ?string $checkoutPaymentStatus, Context $context): void
    {
        switch ($checkoutPaymentStatus) {
            case CheckoutPaymentService::STATUS_CAPTURED:
                $this->orderTransitionService->setTransitionState($order, $settings->getOrderStateForPaidPayment(), $context);

                break;
            case CheckoutPaymentService::STATUS_FAILED:
                $this->orderTransitionService->setTransitionState($order, $settings->getOrderStateForFailedPayment(), $context);

                break;
            case CheckoutPaymentService::STATUS_AUTHORIZED:
                $this->orderTransitionService->setTransitionState($order, $settings->getOrderStateForAuthorizedPayment(), $context);

                break;
            case CheckoutPaymentService::STATUS_VOID:
                $this->orderTransitionService->setTransitionState($order, $settings->getOrderStateForVoidedPayment(), $context);

                break;
            case CheckoutPaymentService::STATUS_REFUNDED:
                $this->orderTransitionService->setTransitionState($order, OrderStates::STATE_CANCELLED, $context);

                break;
            case CheckoutPaymentService::STATUS_PENDING:
                $this->orderTransitionService->setTransitionState($order, OrderStates::STATE_OPEN, $context);

                break;

            default:
                $this->logger->critical('Unknown order status', [
                    'orderId' => $order->getId(),
                    'status' => $checkoutPaymentStatus,
                ]);

                throw new Exception(sprintf('Updating Status of Order not possible for status: %s', $checkoutPaymentStatus));
        }
    }

    public function getOrderByOrderNumber(string $orderNumber, Context $context): OrderEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));

        $order = $this->orderRepository->search($criteria, $context)->first();

        if (!$order instanceof OrderEntity) {
            $this->logger->critical(
                sprintf('Could not fetch order with order number %s', $orderNumber)
            );

            throw new OrderNotFoundException($orderNumber);
        }

        return $order;
    }
}
