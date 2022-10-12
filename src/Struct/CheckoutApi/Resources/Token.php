<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\CheckoutApi\Resources;

use Shopware\Core\Framework\Struct\Struct;

class Token extends Struct
{
    protected string $type;

    protected string $token;

    public function getType(): string
    {
        return $this->type;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
