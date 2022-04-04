<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Event;

use CheckoutCom\Shopware6\Struct\CustomFields\CustomerCustomFieldsStruct;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class CustomerSaveCardTokenEvent extends Event
{
    private CustomerCustomFieldsStruct $customerCustomFields;

    private string $cardToken;

    private SalesChannelContext $context;

    public function __construct(CustomerCustomFieldsStruct $customerCustomFields, string $cardToken, SalesChannelContext $context)
    {
        $this->customerCustomFields = $customerCustomFields;
        $this->cardToken = $cardToken;
        $this->context = $context;
    }

    public function getCustomerCustomFields(): CustomerCustomFieldsStruct
    {
        return $this->customerCustomFields;
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
