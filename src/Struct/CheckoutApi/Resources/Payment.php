<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\CheckoutApi\Resources;

use CheckoutCom\Shopware6\Service\CheckoutApi\CheckoutPaymentService;
use Shopware\Core\Framework\Struct\Struct;

class Payment extends Struct
{
    protected string $id;

    protected ?string $action_id = null;

    protected bool $approved = false;

    protected ?string $status = null;

    protected ?string $reference = null;

    protected ?array $_links = null;

    protected ?string $response_code = null;

    protected ?string $response_summary = null;

    protected ?array $actions = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function getActionId(): ?string
    {
        return $this->action_id;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function isFailed(): bool
    {
        $failedStatus = [
            CheckoutPaymentService::STATUS_DECLINED,
            CheckoutPaymentService::STATUS_CANCELED,
            CheckoutPaymentService::STATUS_EXPIRED,
        ];

        return \in_array($this->status, $failedStatus, true);
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getResponseCode(): ?string
    {
        return $this->response_code;
    }

    public function getResponseSummary(): ?string
    {
        return $this->response_summary;
    }

    public function getLinks(): ?array
    {
        return $this->_links;
    }

    public function getFieldLinkHref(string $field): ?string
    {
        $links = $this->getLinks();

        if (empty($links)) {
            return null;
        }

        if (!\array_key_exists($field, $links)) {
            return null;
        }

        if (!\array_key_exists('href', $links[$field])) {
            return null;
        }

        return $links[$field]['href'];
    }

    public function getRedirectUrl(): ?string
    {
        return $this->getFieldLinkHref('redirect');
    }

    public function getActions(): ?array
    {
        return $this->actions;
    }
}
