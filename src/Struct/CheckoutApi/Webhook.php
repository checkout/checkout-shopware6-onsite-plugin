<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\CheckoutApi;

use Shopware\Core\Framework\Struct\Struct;

class Webhook extends Struct
{
    protected ?string $id = null;

    protected ?string $url = null;

    protected bool $active = true;

    protected ?string $content_type = null;

    protected array $event_types = [];

    protected ?string $authorization = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getContentType(): ?string
    {
        return $this->content_type;
    }

    public function setContentType(?string $content_type): void
    {
        $this->content_type = $content_type;
    }

    public function getEventTypes(): array
    {
        return $this->event_types;
    }

    public function setEventTypes(array $event_types): void
    {
        $this->event_types = $event_types;
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
