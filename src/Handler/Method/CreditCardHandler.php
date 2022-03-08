<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Handler\Method;

use Checkout\Common\PaymentSourceType;
use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Struct\PaymentMethod\DisplayNameTranslationCollection;

class CreditCardHandler extends PaymentHandler
{
    public static function getPaymentMethodType(): string
    {
        return PaymentSourceType::$card;
    }

    public static function getPaymentMethodDisplayName(): DisplayNameTranslationCollection
    {
        $displayNameCollection = new DisplayNameTranslationCollection();
        $displayNameCollection->addLangData('de-DE', 'Kreditkarte');
        $displayNameCollection->addLangData('en-GB', 'Credit card');

        return $displayNameCollection;
    }
}
