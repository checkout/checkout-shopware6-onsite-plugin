<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\Extractor;

use Cko\Shopware6\Service\AddressService;
use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderExtractor extends AbstractOrderExtractor
{
    private LoggerInterface $logger;

    private AddressService $addressService;

    public function __construct(LoggerInterface $loggerService, AddressService $addressService)
    {
        $this->logger = $loggerService;
        $this->addressService = $addressService;
    }

    public function getDecorated(): AbstractOrderExtractor
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @throws Exception
     */
    public function extractOrderNumber(OrderEntity $order): string
    {
        $orderNumber = $order->getOrderNumber();
        if ($orderNumber === null) {
            $this->logger->critical(sprintf('Order number is null with order ID: %s', $order->getId()), [
                'orderId' => $order->getId(),
            ]);

            throw new Exception('Order number is null');
        }

        return $orderNumber;
    }

    public function extractCustomer(OrderEntity $order): OrderCustomerEntity
    {
        $customer = $order->getOrderCustomer();
        if (!$customer instanceof OrderCustomerEntity) {
            $this->logger->critical(
                sprintf('Could not extract customer from order ID %s', $order->getId())
            );

            throw new EntityNotFoundException('Customer of Order', $order->getId());
        }

        return $customer;
    }

    /**
     * @throws Exception
     */
    public function extractBillingAddress(OrderEntity $order, SalesChannelContext $context): OrderAddressEntity
    {
        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress instanceof OrderAddressEntity) {
            $message = sprintf('Could not extract billing from order ID: %s', $order->getId());
            $this->logger->error($message, [
                'function' => __FUNCTION__,
            ]);

            throw new Exception($message);
        }

        return $this->addressService->getOrderAddress(
            $billingAddress->getId(),
            $context->getContext()
        );
    }

    /**
     * @throws Exception
     */
    public function extractShippingAddress(OrderEntity $order, SalesChannelContext $context): OrderAddressEntity
    {
        $delivery = $this->extractOrderDelivery($order);

        $shippingAddress = $delivery->getShippingOrderAddress();
        if (!$shippingAddress instanceof OrderAddressEntity) {
            $message = sprintf('No order shipping address found with order ID: %s', $order->getId());
            $this->logger->error($message, [
                'function' => __FUNCTION__,
            ]);

            throw new Exception($message);
        }

        return $this->addressService->getOrderAddress(
            $shippingAddress->getId(),
            $context->getContext()
        );
    }

    public function extractCurrency(OrderEntity $order): CurrencyEntity
    {
        $currency = $order->getCurrency();
        if (!$currency instanceof CurrencyEntity) {
            $this->logger->critical(
                sprintf('Could not extract currency from order with ID  %s', $order->getId())
            );

            throw new EntityNotFoundException('Currency of Order', $order->getId());
        }

        return $currency;
    }

    public function extractOrderDelivery(OrderEntity $order): OrderDeliveryEntity
    {
        $deliveries = $order->getDeliveries();
        if (!$deliveries instanceof OrderDeliveryCollection) {
            $message = sprintf('Could not extract deliveries from order ID: %s', $order->getId());
            $this->logger->error($message, [
                'function' => __FUNCTION__,
            ]);

            throw new Exception($message);
        }

        $delivery = $deliveries->first();
        if (!$delivery instanceof OrderDeliveryEntity) {
            $message = sprintf('No order delivery found with order ID: %s', $order->getId());
            $this->logger->error($message, [
                'function' => __FUNCTION__,
            ]);

            throw new Exception($message);
        }

        return $delivery;
    }

    public function extractLatestOrderTransaction(OrderEntity $order): OrderTransactionEntity
    {
        $orderTransactions = $order->getTransactions();
        if (!$orderTransactions instanceof OrderTransactionCollection) {
            $this->logger->critical(
                sprintf('The orderTransactions must be instance of OrderTransactionCollection with Order ID: %s', $order->getId())
            );

            throw new InvalidOrderException($order->getId());
        }

        $orderTransaction = $orderTransactions->last();
        if (!$orderTransaction instanceof OrderTransactionEntity) {
            $this->logger->critical(
                sprintf('Could not find an order transaction with Order ID: %s', $order->getId())
            );

            throw new InvalidOrderException($order->getId());
        }

        return $orderTransaction;
    }

    public function extractOrderShippingMethod(OrderEntity $order): ShippingMethodEntity
    {
        $delivery = $this->extractOrderDelivery($order);

        $shippingMethod = $delivery->getShippingMethod();
        if (!$shippingMethod instanceof ShippingMethodEntity) {
            $message = sprintf('No shipping method found with order ID: %s', $order->getId());
            $this->logger->error($message, [
                'function' => __FUNCTION__,
            ]);

            throw new Exception($message);
        }

        return $shippingMethod;
    }
}
