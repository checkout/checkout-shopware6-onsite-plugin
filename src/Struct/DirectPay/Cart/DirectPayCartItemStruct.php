<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\DirectPay\Cart;

use Shopware\Core\Framework\Struct\Struct;

class DirectPayCartItemStruct extends Struct
{
    private string $name;

    private int $quantity;

    private float $price;

    public function __construct(string $name, int $quantity, float $price)
    {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->price = $price;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
