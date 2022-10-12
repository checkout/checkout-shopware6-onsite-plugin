<?php
declare(strict_types=1);

namespace Cko\Shopware6\Struct\CheckoutApi;

use Shopware\Core\Framework\Struct\Struct;

class Webhook extends Struct
{
    protected ?string $id = null;

    protected ?string $authorization = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getAuthorization(): ?string
    {
        return $this->authorization;
    }

    public function setAuthorization(?string $authorization): void
    {
        $this->authorization = $authorization;
    }
}
