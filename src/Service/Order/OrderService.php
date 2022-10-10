<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Order;

use CheckoutCom\Shopware6\Exception\OrderNotFoundException;
use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use CheckoutCom\Shopware6\Service\Transition\AbstractOrderTransitionService;
use CheckoutCom\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService as CoreOrderService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Util\FloatComparator;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;

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

    private EntityRepositoryInterface $orderAddressRepository;

    private EntityRepositoryInterface $orderDeliveryRepository;

    private CoreOrderService $coreOrderService;

    private AbstractOrderTransitionService $orderTransitionService;

    public function __construct(
        LoggerInterface $logger,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderAddressRepository,
        EntityRepositoryInterface $orderDeliveryRepository,
        CoreOrderService $coreOrderService,
        AbstractOrderTransitionService $orderTransitionService
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->coreOrderService = $coreOrderService;
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

    /**
     * @throws Exception
     */
    public function createOrder(
        SalesChannelContext $context,
        DataBag $data,
        RequestDataBag $shippingContact,
        SalutationEntity $salutation,
        CountryEntity $country,
        ?CountryStateEntity $countryState
    ): OrderEntity {
        $orderId = $this->coreOrderService->createOrder($data, $context);

        $order = $this->getOrder(
            $context->getContext(),
            $orderId,
            [
                'deliveries.shippingOrderAddress',
            ]
        );

        $orderDeliveries = $order->getDeliveries();
        if (!$orderDeliveries instanceof OrderDeliveryCollection) {
            $message = sprintf('Order delivery collection did not find with order ID: %s', $orderId);
            $this->logger->critical($message);

            throw new Exception($message);
        }

        // Always make sure use the data from request
        foreach ($orderDeliveries as $orderDelivery) {
            $orderShippingAddress = $orderDelivery->getShippingOrderAddress();
            if (!$orderShippingAddress instanceof OrderAddressEntity) {
                continue;
            }

            $updatedData = [
                'id' => $orderShippingAddress->getId(),
                'orderId' => $orderId,
                'salutationId' => $salutation->getId(),
                'firstName' => $shippingContact->get('firstName'),
                'lastName' => $shippingContact->get('lastName'),
                'street' => $shippingContact->get('street'),
                'zipcode' => $shippingContact->get('zipCode'),
                'city' => $shippingContact->get('city'),
                'countryId' => $country->getId(),
                'countryStateId' => empty($countryState) ? null : $countryState->getId(),
            ];

            if ($orderShippingAddress->getId() === $order->getBillingAddressId()) {
                // Create new order shipping address ID
                $orderShippingAddressId = Uuid::randomHex();
                $updatedData['id'] = $orderShippingAddressId;

                // Also update the order delivery with new order shipping address ID
                $this->orderDeliveryRepository->update([
                    [
                        'id' => $orderDelivery->getId(),
                        'shippingOrderAddressId' => $orderShippingAddressId,
                    ],
                ], $context->getContext());
            }

            $this->orderAddressRepository->upsert([$updatedData], $context->getContext());
        }

        return $order;
    }

    public function updateOrder(
        array $data,
        Context $context
    ): void {
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data): void {
            $this->orderRepository->update([$data], $context);
        });
    }

    public function getOrder(Context $context, string $orderId, array $associations = [], ?callable $criteriaCallback = null): OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->setLimit(1);
        foreach ($associations as $association) {
            $criteria->addAssociation($association);
        }

        if ($criteriaCallback !== null) {
            $criteriaCallback($criteria);
        }

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
    public function updateCheckoutCustomFields(OrderEntity $order, OrderCustomFieldsStruct $orderCustomFields, Context $context): void
    {
        $this->logger->debug('Updating order checkout custom fields', [
            'orderId' => $order->getId(),
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
        ], $context);
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
            // We don't need to change order status for this payment status
            case CheckoutPaymentService::STATUS_REFUNDED:
            case CheckoutPaymentService::STATUS_PARTIALLY_REFUNDED:
            case CheckoutPaymentService::STATUS_PENDING:
            case CheckoutPaymentService::STATUS_CANCELED:
            case CheckoutPaymentService::STATUS_EXPIRED:
            case CheckoutPaymentService::STATUS_DECLINED:
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
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('deliveries.shippingMethod');
        $criteria->addAssociation('deliveries.positions.orderLineItem');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('deliveries.shippingOrderAddress.countryState');
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

    public function isOnlyHaveShippingCosts(
        OrderEntity $order,
        LineItemCollection $requestLineItems,
        LineItemCollection $shippingCostsLineItems
    ): bool {
        $totalExcludingShippingCosts = abs($order->getPrice()->getRawTotal()) - abs($shippingCostsLineItems->getPrices()->sum()->getTotalPrice());

        return FloatComparator::equals(
            abs($totalExcludingShippingCosts),
            abs($requestLineItems->getPrices()->sum()->getTotalPrice())
        );
    }
}
