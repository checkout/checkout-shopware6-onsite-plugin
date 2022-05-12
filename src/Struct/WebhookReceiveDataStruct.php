<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct;

use Shopware\Core\Framework\Struct\Struct;

class WebhookReceiveDataStruct extends Struct
{
    protected string $id;

    protected string $type;

    protected string $created_on;

    protected string $reference;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getCreatedOn(): string
    {
        return $this->created_on;
    }

    public function setCreatedOn(string $created_on): void
    {
        $this->created_on = $created_on;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }
}
