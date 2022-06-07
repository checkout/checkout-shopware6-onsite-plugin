<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct;

use CheckoutCom\Shopware6\Exception\CheckoutComException;
use Shopware\Core\Framework\Struct\Collection;
use Shopware\Core\Framework\Struct\Struct;

/**
 * This abstract class is used to structure the data returned from the API.
 * Because the Shopware return some additional data, and it causes the errors,
 * that is why need to remove those data
 * Example:
 * - extensions
 * - apiAlias
 */
abstract class ApiStruct extends Struct
{
    private const REMOVED_FIELDS = ['extensions'];

    public function toApiJson(): array
    {
        $data = $this->getApiJson($this->getVars());

        // Only add the apiAlias on the first level
        $data['apiAlias'] = $this->getApiAlias();

        return $data;
    }

    private function getApiJson(array $structData): array
    {
        foreach (self::REMOVED_FIELDS as $field) {
            if (!\array_key_exists($field, $structData)) {
                continue;
            }

            unset($structData[$field]);
        }

        foreach ($structData as $key => $structItem) {
            if ($structItem instanceof Collection) {
                $structData[$key] = $this->getCollectionApiJson($structItem);

                continue;
            }

            if ($structItem instanceof ApiStruct) {
                $structData[$key] = $this->getApiJson($structItem->getVars());
            }
        }

        return $structData;
    }

    private function getCollectionApiJson(Collection $collection): array
    {
        $data = [];

        foreach ($collection->getElements() as $key => $item) {
            if (!$item instanceof ApiStruct) {
                throw new CheckoutComException('Collection item must be an instance of ApiStruct');
            }

            $data[$key] = $item->getApiJson($item->getVars());
        }

        return $data;
    }
}
