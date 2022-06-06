<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\CheckoutApi\Resources;

use Shopware\Core\Framework\Struct\Struct;

class PaymentSource extends Struct
{
    protected ?string $id = null;

    protected ?string $type = null;

    protected ?string $scheme = null;

    protected ?string $last4 = null;

    protected ?string $fingerprint = null;

    protected ?string $card_type = null;

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

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function setScheme(?string $scheme): void
    {
        $this->scheme = $scheme;
    }

    public function getLast4(): ?string
    {
        return $this->last4;
    }

    public function setLast4(?string $last4): void
    {
        $this->last4 = $last4;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(?string $fingerprint): void
    {
        $this->fingerprint = $fingerprint;
    }

    public function getCardType(): ?string
    {
        return $this->card_type;
    }

    public function setCardType(?string $card_type): void
    {
        $this->card_type = $card_type;
    }
}
