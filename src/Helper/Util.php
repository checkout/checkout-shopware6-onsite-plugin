<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Helper;

use Exception;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;

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

    public static function buildSideEnterStateEventName(string $technicalName, string $stateName): string
    {
        return implode('.', [
            StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER,
            $technicalName,
            $stateName,
        ]);
    }
}
