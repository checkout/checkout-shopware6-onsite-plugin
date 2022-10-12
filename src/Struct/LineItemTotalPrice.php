<?php
declare(strict_types=1);

namespace Cko\Shopware6\Struct;

use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Framework\Struct\Collection;
use Shopware\Core\Framework\Struct\Struct;

class LineItemTotalPrice extends Struct
{
    protected CartPrice $price;

    /**
     * @var OrderLineItemCollection|LineItemCollection|null
     */
    protected ?Collection $lineItems = null;

    /**
     * @var OrderDeliveryCollection|DeliveryCollection|null
     */
    protected ?Collection $deliveries = null;

    public function getPrice(): CartPrice
    {
        return $this->price;
    }

    public function setPrice(CartPrice $price): void
    {
        $this->price = $price;
    }

    /**
     * @return OrderLineItemCollection|LineItemCollection|null
     */
    public function getLineItems(): ?Collection
    {
        return $this->lineItems;
    }

    /**
     * @param OrderLineItemCollection|LineItemCollection|null $lineItems
     */
    public function setLineItems(?Collection $lineItems): void
    {
        $this->lineItems = $lineItems;
    }

    /**
     * @return DeliveryCollection|OrderDeliveryCollection|null
     */
    public function getDeliveries()
    {
        return $this->deliveries;
    }

    /**
     * @param DeliveryCollection|OrderDeliveryCollection|null $deliveries
     */
    public function setDeliveries($deliveries): void
    {
        $this->deliveries = $deliveries;
    }
}
