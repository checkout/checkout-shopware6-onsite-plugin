<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Event;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class CustomerSaveCardTokenEvent extends Event
{
    private CustomerEntity $customer;

    private string $cardToken;

    private SalesChannelContext $context;

    public function __construct(CustomerEntity $customer, string $cardToken, SalesChannelContext $context)
    {
        $this->customer = $customer;
        $this->cardToken = $cardToken;
        $this->context = $context;
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function getCardToken(): string
    {
        return $this->cardToken;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('customer', new EntityType(CustomerDefinition::class))
            ->add('cardToken', new ScalarValueType(ScalarValueType::TYPE_STRING));
    }
}
