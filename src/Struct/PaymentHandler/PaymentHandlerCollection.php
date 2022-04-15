<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentHandler;

use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\Util;
use Shopware\Core\Framework\Struct\Collection;

class PaymentHandlerCollection extends Collection
{
    public function getByPaymentType(string $paymentType): ?PaymentHandler
    {
        return $this->filter(function ($paymentHandler) use ($paymentType) {
            /* @var PaymentHandler $paymentHandler */
            return Util::handleCallUserFunc($paymentHandler->getClassName() . '::getPaymentMethodType', false) === $paymentType;
        })->first();
    }

    public function hasHandlerIdentifier(string $handlerIdentifier): bool
    {
        /** @var PaymentHandler $element */
        foreach ($this->getElements() as $element) {
            if ($element->getClassName() === $handlerIdentifier) {
                return true;
            }
        }

        return false;
    }

    protected function getExpectedClass(): string
    {
        return PaymentHandler::class;
    }
}
