<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentHandler;

use CheckoutCom\Shopware6\Handler\PaymentHandler;
use Shopware\Core\Framework\Struct\Collection;

class PaymentHandlerCollection extends Collection
{
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
