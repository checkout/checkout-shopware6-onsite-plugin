<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Twig;

use Cko\Shopware6\Service\CountryService;
use Cko\Shopware6\Twig\CountryStateCodeExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Twig\TwigFilter;

class CountryStateCodeExtensionTest extends TestCase
{
    /**
     * @var CountryService|MockObject
     */
    private $countryService;

    private CountryStateCodeExtension $countryStateCodeExtension;

    public function setUp(): void
    {
        $this->countryService = $this->createMock(CountryService::class);
        $this->countryStateCodeExtension = new CountryStateCodeExtension(
            $this->countryService
        );
    }

    public function testGetFunctionsReturnsArrayWithTwigFunction(): void
    {
        $firstTwigFilter = $this->countryStateCodeExtension->getFilters()[0];
        static::assertInstanceOf(TwigFilter::class, $firstTwigFilter);
    }

    public function testIfFunctionContainsStaticCall(): void
    {
        $countryStateCode = array_filter($this->countryStateCodeExtension->getFilters(), static function ($filter) {
            return $filter->getName() === 'countryStateCode';
        });

        static::assertCount(1, $countryStateCode);
    }

    public function testGetCountryStateCodeOfNullState(): void
    {
        $result = $this->countryStateCodeExtension->getCountryStateCode(null);
        static::assertNull($result);
    }

    public function testGetCountryStateCode(): void
    {
        $countryState = new CountryStateEntity();
        $countryState->setId('foo');
        $countryState->setShortCode('DE-EN');

        $this->countryService->expects(static::once())
            ->method('getCountryStateCode');

        $this->countryStateCodeExtension->getCountryStateCode($countryState);
    }
}
