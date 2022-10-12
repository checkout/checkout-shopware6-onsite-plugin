<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\Customer;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Struct\Struct;

class RegisterAndLoginGuestStruct extends Struct
{
    protected CustomerEntity $customer;

    protected string $contextToken;

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getContextToken(): string
    {
        return $this->contextToken;
    }

    public function setContextToken(string $contextToken): void
    {
        $this->contextToken = $contextToken;
    }
}
