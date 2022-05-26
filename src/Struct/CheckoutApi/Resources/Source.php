<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\CheckoutApi\Resources;

use Shopware\Core\Framework\Struct\Struct;

class Source extends Struct
{
    protected string $id;

    protected string $type;

    protected string $response_code;

    protected string $response_data;

    protected string $customer;

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

    public function getResponseCode(): string
    {
        return $this->response_code;
    }

    public function setResponseCode(string $response_code): void
    {
        $this->response_code = $response_code;
    }

    public function getResponseData(): string
    {
        return $this->response_data;
    }

    public function setResponseData(string $response_data): void
    {
        $this->response_data = $response_data;
    }

    public function getCustomer(): string
    {
        return $this->customer;
    }

    public function setCustomer(string $customer): void
    {
        $this->customer = $customer;
    }
}
