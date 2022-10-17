<?php declare(strict_types=1);

namespace Cko\Shopware6\Service;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class PaymentMethodCookieProvider implements CookieProviderInterface
{
    private const entitiesCookieGroupRequired = [
        [
            'snippet_name' => 'checkoutCom.cookie.paymentMethodDescription',
            'cookie' => 'cko-payment',
        ],
    ];

    private CookieProviderInterface $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    public function getCookieGroups(): array
    {
        return array_map(function ($cookie) {
            if ($cookie['snippet_name'] !== 'cookie.groupRequired') {
                return $cookie;
            }

            $cookie['entries'] = array_merge(
                $cookie['entries'],
                self::entitiesCookieGroupRequired
            );

            return $cookie;
        }, $this->originalService->getCookieGroups());
    }
}
