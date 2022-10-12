<?php declare(strict_types=1);

namespace Cko\Shopware6\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CardIconExtension extends AbstractExtension
{
    public const PATH_CARD_ICONS = 'static/img/card-icons';
    public const CHECKOUT_BUNDLE = '@CkoShopware6';
    public const CARD_ICONS_MAPPING = [
        'amex' => 'american express.svg',
        'dinersclub' => 'diners club.svg',
        'discover' => 'discover.svg',
        'jcb' => 'jcb.svg',
        'mada' => 'mada.svg',
        'maestro' => 'maestro.svg',
        'mastercard' => 'mastercard.svg',
        'visa' => 'visa.svg',
    ];

    private Packages $packages;

    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('checkoutCardIcon', [$this, 'getCheckoutCardIcon'])];
    }

    public function getCheckoutCardIcon(string $cardScheme): ?string
    {
        $cardIcon = $this->getMappingCardIcon($cardScheme);
        if (!\is_string($cardIcon)) {
            return null;
        }

        $path = sprintf('%s/%s', self::PATH_CARD_ICONS, $cardIcon);

        return $this->packages->getUrl($path, self::CHECKOUT_BUNDLE);
    }

    private function getMappingCardIcon(string $cardScheme): ?string
    {
        $formattedCardScheme = trim(strtolower($cardScheme));

        if (!\array_key_exists($formattedCardScheme, self::CARD_ICONS_MAPPING)) {
            return null;
        }

        return self::CARD_ICONS_MAPPING[$formattedCardScheme];
    }
}
