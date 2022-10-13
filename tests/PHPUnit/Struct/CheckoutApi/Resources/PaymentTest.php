<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Struct\CheckoutApi\Resources;

use Cko\Shopware6\Struct\CheckoutApi\Resources\Payment;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    /**
     * @dataProvider getFieldLinkHrefProvider
     */
    public function testGetFieldLinkHref(string $field, ?array $links, ?string $expected): void
    {
        $payment = new Payment();
        $payment->assign([
            '_links' => $links,
        ]);

        static::assertSame($expected, $payment->getFieldLinkHref($field));
    }

    public function getFieldLinkHrefProvider(): array
    {
        return [
            'Test empty _links' => [
                'any_key',
                null,
                null,
            ],
            'Test _links does not has field key' => [
                'any_key',
                [
                    'other_key' => [
                        'href' => 'http://example.com',
                    ],
                ],
                null,
            ],
            'Test _links has field key but field key does not has href key' => [
                'any_key',
                [
                    'any_key' => [
                        'not exists' => 'http://example.com',
                    ],
                ],
                null,
            ],
            'Test _links has field key and field key also has href key' => [
                'any_key',
                [
                    'any_key' => [
                        'href' => 'http://example.com',
                    ],
                ],
                'http://example.com',
            ],
        ];
    }
}
