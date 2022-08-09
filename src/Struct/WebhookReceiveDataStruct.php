<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct;

use Shopware\Core\Framework\Struct\Struct;

class WebhookReceiveDataStruct extends Struct
{
    protected ?string $id = null;

    protected ?string $type = null;

    protected ?string $created_on = null;

    protected ?string $reference = null;

    protected ?string $actionId = null;

    protected ?int $amount = null;

    protected ?string $currency = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getCreatedOn(): ?string
    {
        return $this->created_on;
    }

    public function setCreatedOn(?string $created_on): void
    {
        $this->created_on = $created_on;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): void
    {
        $this->reference = $reference;
    }

    public function getActionId(): ?string
    {
        return $this->actionId;
    }

    public function setActionId(?string $actionId): void
    {
        $this->actionId = $actionId;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }
}
