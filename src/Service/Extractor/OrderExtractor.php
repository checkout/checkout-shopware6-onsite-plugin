<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service\Extractor;

use CheckoutCom\Shopware6\Service\CustomerService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderExtractor
{
    private LoggerInterface $logger;

    private CustomerService $customerService;

    public function __construct(LoggerInterface $loggerService, CustomerService $customerService)
    {
        $this->logger = $loggerService;
        $this->customerService = $customerService;
    }

    public function extractCustomer(OrderEntity $order, SalesChannelContext $context): CustomerEntity
    {
        $customer = $order->getOrderCustomer();
        if (!$customer instanceof OrderCustomerEntity) {
            $this->logger->critical(
                sprintf('Could not extract customer from order with ID %s', $order->getId())
            );

            throw new EntityNotFoundException('Customer of Order', $order->getId());
        }

        $customerId = $customer->getCustomerId();
        if ($customerId === null) {
            $this->logger->critical(
                sprintf('Could not found customer ID from Order Customer Entity with order ID %s', $order->getId())
            );

            throw new EntityNotFoundException('Customer of OrderCustomer', $order->getId());
        }

        return $this->customerService->getCustomer(
            $customerId,
            $context
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
}
