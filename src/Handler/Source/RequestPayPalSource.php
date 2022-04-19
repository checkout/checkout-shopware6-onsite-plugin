<?php
declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Source;

use Checkout\Common\PaymentSourceType;
use Checkout\Payments\Source\AbstractRequestSource;

class RequestPayPalSource extends AbstractRequestSource
{
    public string $invoice_number;

    public function __construct()
    {
        parent::__construct(PaymentSourceType::$paypal);
    }
}
