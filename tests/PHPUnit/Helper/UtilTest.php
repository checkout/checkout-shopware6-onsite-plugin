<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Helper;

use Cko\Shopware6\Helper\Util;
use Exception;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public const TEST_STRING = 'test';

    public static function fakeStaticFunction(): string
    {
        return self::TEST_STRING;
    }

    public function testCallUserFunctionMustThrowException(): void
    {
        static::expectException(Exception::class);
        Util::handleCallUserFunc('Any functions not found must throw exception');
    }

    /**
     * @dataProvider callUserFunctionProvider
     */
    public function testCallUserFunction($expected, string $callbackFunction, $defaultValue = null): void
    {
        $result = Util::handleCallUserFunc($callbackFunction, false, $defaultValue);
        static::assertSame($expected, $result);
    }

    public function callUserFunctionProvider(): array
    {
        return [
            'Test not found function' => [
                null,
                'anything function not found',
            ],
            'Test not found function but with default value' => [
                'default value',
                'anything function not found',
                'default value',
            ],
            'Test return expect value' => [
                self::TEST_STRING,
                UtilTest::class . '::fakeStaticFunction',
            ],
        ];
    }
}
