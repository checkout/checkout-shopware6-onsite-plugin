<?php declare(strict_types=1);

namespace Cko\Shopware6\Helper;

use Exception;
use Shopware\Core\Framework\Struct\Struct;

class Util
{
    /**
     * @param callable|string   $callback
     * @param mixed|string|null $default
     *
     * @throws Exception
     *
     * @return mixed|string|null
     */
    public static function handleCallUserFunc($callback, bool $throwable = true, $default = null)
    {
        if (\is_callable($callback)) {
            return \call_user_func($callback);
        }

        if ($throwable) {
            throw new Exception(sprintf('%s is not callable', $callback));
        }

        return $default;
    }

    public static function serializeStruct(Struct $struct): array
    {
        $encodeStruct = (string) json_encode($struct);

        return json_decode($encodeStruct, true);
    }
}
