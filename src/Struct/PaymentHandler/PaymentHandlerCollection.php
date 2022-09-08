<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentHandler;

use CheckoutCom\Shopware6\Handler\PaymentHandler;
use CheckoutCom\Shopware6\Helper\Util;
use Shopware\Core\Framework\Struct\Collection;

/**
 * @method PaymentHandler[]    getIterator()
 * @method PaymentHandler[]    getElements()
 * @method PaymentHandler|null get(string $key)
 * @method PaymentHandler|null first()
 * @method PaymentHandler|null last()
 */
class PaymentHandlerCollection extends Collection
{
    public function getByPaymentType(string $paymentType): ?PaymentHandler
    {
        return $this->filter(function (PaymentHandler $paymentHandler) use ($paymentType) {
            return Util::handleCallUserFunc($paymentHandler->getClassName() . '::getPaymentMethodType', false) === $paymentType;
        })->first();
    }

    public function getByHandlerIdentifier(string $handlerIdentifier): ?PaymentHandler
    {
        return $this->filter(function (PaymentHandler $paymentHandler) use ($handlerIdentifier) {
            return $paymentHandler->getClassName() === $handlerIdentifier;
        })->first();
    }

    public function hasHandlerIdentifier(string $handlerIdentifier): bool
    {
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
