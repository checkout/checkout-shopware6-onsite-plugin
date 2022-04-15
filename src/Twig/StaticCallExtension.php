<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Twig;

use CheckoutCom\Shopware6\Helper\Util;
use Exception;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StaticCallExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('staticFuncCall', [$this, 'staticFuncCall']),
        ];
    }

    /**
     * @param mixed|string|null $default
     *
     * @throws Exception
     */
    public function staticFuncCall(string $callback, $default = null): ?string
    {
        return Util::handleCallUserFunc($callback, false, $default);
    }
}
