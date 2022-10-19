<?php declare(strict_types=1);

namespace Cko\Shopware6\Service;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class PaymentMethodCookieProvider implements CookieProviderInterface
{
    private const ANALYTICS_COOKIE = [
        'snippet_name' => 'checkoutCom.cookie.analytics.groupNameLabel',
        'snippet_description' => 'checkoutCom.cookie.analytics.groupNameDescription',
        'entries' => [
            [
                'snippet_name' => 'checkoutCom.cookie.analytics.googlePayLabel',
                'cookie' => 'cko-payment_google-pay',
                'value' => '1',
            ],
        ],
    ];

    private CookieProviderInterface $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    public function getCookieGroups(): array
    {
        return array_merge(
            $this->originalService->getCookieGroups(),
            [
                self::ANALYTICS_COOKIE,
            ]
        );
    }
}
