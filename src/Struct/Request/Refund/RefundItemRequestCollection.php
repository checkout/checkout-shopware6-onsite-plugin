<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\Request\Refund;

use Shopware\Core\Framework\Struct\Collection;

class RefundItemRequestCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return RefundItemRequest::class;
    }
}
