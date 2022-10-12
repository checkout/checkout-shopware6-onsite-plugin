<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\Customer;

use Shopware\Core\Framework\Struct\Struct;

class CustomerSourceCustomFieldsStruct extends Struct
{
    protected ?CustomerSourceCollection $card = null;

    public function getSourceByType(?string $type): ?CustomerSourceCollection
    {
        if ($type === null) {
            return null;
        }

        return $this->$type ?? null;
    }
}
