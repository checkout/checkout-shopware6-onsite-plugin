<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Twig;

use CheckoutCom\Shopware6\Twig\StaticCallExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class StaticCallExtensionTest extends TestCase
{
    public const TEST_DATA = 'testData';

    private StaticCallExtension $staticCallExtension;

    public function setUp(): void
    {
        $this->staticCallExtension = new StaticCallExtension();
    }

    public static function staticToTest(): string
    {
        return self::TEST_DATA;
    }

    public function testGetFunctionsReturnsArrayWithTwigFunction(): void
    {
        $firstTwigFunction = $this->staticCallExtension->getFunctions()[0];
        static::assertInstanceOf(TwigFunction::class, $firstTwigFunction);
    }

    public function testIfFunctionContainsStaticCall(): void
    {
        $staticCallFunction = array_filter($this->staticCallExtension->getFunctions(), static function ($filter) {
            return $filter->getName() === 'staticFuncCall';
        });

        static::assertCount(1, $staticCallFunction);
    }

    /**
     * @dataProvider staticFunctionProvider
     */
    public function testStaticFunction(string $classFunctionName, $expected): void
    {
        $result = $this->staticCallExtension->staticFuncCall($classFunctionName, $expected);
        static::assertEquals($expected, $result);
    }

    public function staticFunctionProvider(): array
    {
        return [
            'Test expect data' => [
                StaticCallExtensionTest::class . '::staticToTest',
                self::TEST_DATA,
            ],
            'Test function not found ' => [
                StaticCallExtensionTest::class . '::functionNotFound',
                'any default value',
            ],
        ];
    }
}
