<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Customer;

use CheckoutCom\Shopware6\Struct\CheckoutApi\Resources\PaymentSource;
use Shopware\Core\Framework\Struct\Collection;

class CustomerSourceCollection extends Collection
{
    public function hasFingerPrint(?string $fingerPrint): bool
    {
        if (empty($fingerPrint)) {
            return false;
        }

        /** @var PaymentSource $element */
        foreach ($this->getElements() as $element) {
            if ($element->getFingerprint() !== $fingerPrint) {
                continue;
            }

            return true;
        }

        return false;
    }

    protected function getExpectedClass(): string
    {
        return PaymentSource::class;
    }
}
