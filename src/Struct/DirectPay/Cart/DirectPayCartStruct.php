<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\DirectPay\Cart;

use Shopware\Core\Framework\Struct\Struct;

class DirectPayCartStruct extends Struct
{
    protected DirectPayCartItemCollection $lineItems;

    protected DirectPayCartItemCollection $shipping;

    protected ?DirectPayCartItemStruct $tax = null;

    public function __construct(DirectPayCartItemCollection $lineItems, DirectPayCartItemCollection $shipping)
    {
        $this->lineItems = $lineItems;
        $this->shipping = $shipping;
    }

    public function getLineItems(): DirectPayCartItemCollection
    {
        return $this->lineItems;
    }

    public function getShipping(): DirectPayCartItemCollection
    {
        return $this->shipping;
    }

    public function getTax(): ?DirectPayCartItemStruct
    {
        return $this->tax;
    }

    public function getTotalAmount(): float
    {
        $amount = $this->getLineItemAmount();
        $amount += $this->getShippingAmount();

        return $amount;
    }

    public function getLineItemAmount(): float
    {
        return $this->getLineItems()->getTotalAmount();
    }

    public function getShippingAmount(): float
    {
        return $this->getShipping()->getTotalAmount();
    }

    public function addLineItem(?string $name, int $quantity, float $price): void
    {
        $this->lineItems->add(new DirectPayCartItemStruct($name ?? '', $quantity, $price));
    }

    public function addShipping(?string $name, float $price): void
    {
        $this->shipping->add(new DirectPayCartItemStruct($name ?? '', 1, $price));
    }

    public function setTax(float $price): void
    {
        $this->tax = new DirectPayCartItemStruct('', 1, $price);
    }
}
