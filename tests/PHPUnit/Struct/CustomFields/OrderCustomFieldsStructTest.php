<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Struct\CustomFields;

use CheckoutCom\Shopware6\Struct\CustomFields\OrderCustomFieldsStruct;
use PHPUnit\Framework\TestCase;

class OrderCustomFieldsStructTest extends TestCase
{
    /**
     * @dataProvider getCheckoutReturnUrlProvider
     */
    public function testGetCheckoutReturnUrl(?string $checkoutReturnUrl, ?string $transactionReturnUrl, $expected): void
    {
        $orderCustomFieldsStruct = new OrderCustomFieldsStruct();
        $orderCustomFieldsStruct->setCheckoutReturnUrl($checkoutReturnUrl);
        $orderCustomFieldsStruct->setTransactionReturnUrl($transactionReturnUrl);

        static::assertSame($expected, $orderCustomFieldsStruct->getCheckoutReturnUrl());
    }

    public function getCheckoutReturnUrlProvider(): array
    {
        return [
            'Test empty checkoutReturnUrl & transactionReturnUrl, it has to return null' => [
                null,
                null,
                null,
            ],
            'Test empty checkoutReturnUrl but transactionReturnUrl has value, it has to return transactionReturnUrl' => [
                null,
                '1234',
                '1234',
            ],
            'Test checkoutReturnUrl has value, it has to return transactionReturnUrl' => [
                '2345',
                '1234',
                '2345',
            ],
        ];
    }
}
