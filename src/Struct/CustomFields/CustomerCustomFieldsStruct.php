<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\CustomFields;

use Shopware\Core\Framework\Struct\Struct;

class CustomerCustomFieldsStruct extends Struct
{
    protected ?string $cardToken = null;

    public function getCardToken(): ?string
    {
        return $this->cardToken;
    }

    public function setCardToken(?string $cardToken): void
    {
        $this->cardToken = $cardToken;
    }
}
