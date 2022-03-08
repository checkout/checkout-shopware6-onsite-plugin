<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Helper;

use Exception;

class Util
{
    /**
     * @param callable|string $callback
     *
     * @throws Exception
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
}
