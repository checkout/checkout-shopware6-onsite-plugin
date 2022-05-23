<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod\Klarna;

use Shopware\Core\Framework\Struct\Struct;

class AssetUrlStruct extends Struct
{
    protected string $descriptive;

    protected string $standard;

    public function getDescriptive(): string
    {
        return $this->descriptive;
    }

    public function setDescriptive(string $descriptive): void
    {
        $this->descriptive = $descriptive;
    }

    public function getStandard(): string
    {
        return $this->standard;
    }

    public function setStandard(string $standard): void
    {
        $this->standard = $standard;
    }
}
