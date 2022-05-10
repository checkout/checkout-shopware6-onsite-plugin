<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Source;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Source\AbstractRequestSource;

class RequestPrzelewy24Source extends AbstractRequestSource
{
    public string $payment_country = 'PL';

    public string $account_holder_name;

    public string $account_holder_email;

    public function __construct()
    {
        parent::__construct(PaymentSourceType::$przelewy24);
    }
}
