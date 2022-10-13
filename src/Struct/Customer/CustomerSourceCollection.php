<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\Customer;

use Cko\Shopware6\Struct\CheckoutApi\Resources\PaymentSource;
use Shopware\Core\Framework\Struct\Collection;

/**
 * @method PaymentSource[]    getIterator()
 * @method PaymentSource[]    getElements()
 * @method PaymentSource|null get(string $key)
 * @method PaymentSource|null first()
 * @method PaymentSource|null last()
 */
class CustomerSourceCollection extends Collection
{
    public function hasFingerPrint(?string $fingerPrint): bool
    {
        if (empty($fingerPrint)) {
            return false;
        }

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
