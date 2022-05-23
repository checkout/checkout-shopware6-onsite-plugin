<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct;

use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
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
}
