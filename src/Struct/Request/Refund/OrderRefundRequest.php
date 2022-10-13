<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\Request\Refund;

use Shopware\Core\Framework\Struct\Struct;

class OrderRefundRequest extends Struct
{
    protected ?string $orderId = null;

    protected ?RefundItemRequestCollection $items = null;

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getItems(): ?RefundItemRequestCollection
    {
        return $this->items;
    }

    public function setItems(RefundItemRequestCollection $items): void
    {
        $this->items = $items;
    }
}
