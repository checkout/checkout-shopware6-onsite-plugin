<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Twig;

use Cko\Shopware6\Twig\CardIconExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Twig\TwigFunction;

class CardIconExtensionTest extends TestCase
{
    private CardIconExtension $cardIconExtension;

    /**
     * @var MockObject|Packages
     */
    private $packages;

    public function setUp(): void
    {
        $this->packages = $this->createMock(Packages::class);
        $this->cardIconExtension = new CardIconExtension($this->packages);
    }

    public function testGetFunctionsReturnsArrayWithTwigFunction(): void
    {
        $firstTwigFunction = $this->cardIconExtension->getFunctions()[0];
        static::assertInstanceOf(TwigFunction::class, $firstTwigFunction);
    }

    public function testIfFunctionContainsFunctionName(): void
    {
        $functionName = array_filter($this->cardIconExtension->getFunctions(), static function ($filter) {
            return $filter->getName() === 'checkoutCardIcon';
        });

        static::assertCount(1, $functionName);
    }

    /**
     * @dataProvider getCheckoutCardIconProvider
     */
    public function testGetCheckoutCardIcon(string $cardScheme, bool $inMappingIcon): void
    {
        if ($inMappingIcon) {
            $this->packages->expects(static::once())->method('getUrl')
                ->willReturn('');
        }

        $result = $this->cardIconExtension->getCheckoutCardIcon($cardScheme);

        if ($inMappingIcon) {
            static::assertIsString($result);
        } else {
            static::assertNull($result);
        }
    }

    public function getCheckoutCardIconProvider(): array
    {
        return [
            'Test is not in mapping icon' => [
                'not in mapping',
                false,
            ],
            'Test is in mapping icon' => [
                'amex',
                true,
            ],
        ];
    }
}
