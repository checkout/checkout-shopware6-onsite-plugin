<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Request\Refund;

use Shopware\Core\Framework\Struct\Struct;

class RefundItemRequest extends Struct
{
    protected string $id;

    protected int $returnQuantity;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getReturnQuantity(): int
    {
        return $this->returnQuantity;
    }

    public function setReturnQuantity(int $returnQuantity): void
    {
        $this->returnQuantity = $returnQuantity;
    }
}
