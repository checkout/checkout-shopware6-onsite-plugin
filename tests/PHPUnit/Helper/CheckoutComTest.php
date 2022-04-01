<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Helper;

use Checkout\Common\Address;
use Checkout\Common\Currency;
use Checkout\Common\CustomerRequest;
use Checkout\Payments\ShippingDetails;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

class CheckoutComTest extends TestCase
{
    use OrderTrait;

    public function testBuildAddressExpectThrowException(): void
    {
        static::expectException(Exception::class);
        CheckoutComUtil::buildAddress(null);
    }

    /**
     * @dataProvider customerAddressDataProvider
     */
    public function testBuildAddress(
        string $street,
        string $city,
        string $zip,
        ?string $addressLine1,
        ?string $addressLine2,
        ?string $stateName,
        ?string $countryISO,
        array $expected
    ): void {
        $customerAddress = $this->getCustomerAddressEntity('', '', $street, $city, $zip, $addressLine1, $addressLine2, $stateName, $countryISO);
        $checkoutAddress = CheckoutComUtil::buildAddress($customerAddress);

        static::assertInstanceOf(Address::class, $checkoutAddress);
        static::assertSame([
            $checkoutAddress->address_line1,
            $checkoutAddress->city,
            $checkoutAddress->zip,
            $checkoutAddress->address_line2,
            $checkoutAddress->state,
            $checkoutAddress->country,
        ], $expected);

        $this->testBuildShipDetail($customerAddress);
    }

    public function testBuildCustomer(): void
    {
        $firstName = 'FirstName';
        $lastName = 'LastName';
        $email = 'email@test.com';
        $fullName = sprintf('%s %s', $firstName, $lastName);
        $customer = $this->getCustomerEntity($firstName, $lastName, $email);
        $checkoutCustomer = CheckoutComUtil::buildCustomer($customer);

        static::assertInstanceOf(CustomerRequest::class, $checkoutCustomer);
        static::assertSame($fullName, $checkoutCustomer->name);
        static::assertSame($email, $checkoutCustomer->email);
    }

    /**
     * @dataProvider formatPriceCheckoutProvider
     */
    public function testFormatPriceCheckout(int $expected, float $price, string $currency): void
    {
        $result = CheckoutComUtil::formatPriceCheckout($price, $currency);
        static::assertSame($expected, $result);
    }

    public function customerAddressDataProvider(): array
    {
        // Expected values
        // address_line1, city, zip, address_line2, state, country
        return [
            'Address Without address 1' => [
                'Street',
                'City',
                'Zip',
                null,
                'Address 2',
                'StateName',
                'Country ISO',
                // Expected
                [
                    'Street',
                    'City',
                    'Zip',
                    'Address 2',
                    'StateName',
                    'Country ISO',
                ],
            ],
            'Address Without address 2' => [
                'Street',
                'City',
                'Zip',
                'Address 1',
                null,
                'StateName',
                'Country ISO',
                // Expected
                [
                    'Street',
                    'City',
                    'Zip',
                    'Address 1',
                    'StateName',
                    'Country ISO',
                ],
            ],
            'Address Without address 1,2' => [
                'Street',
                'City',
                'Zip',
                null,
                null,
                'StateName',
                'Country ISO',
                // Expected
                [
                    'Street',
                    'City',
                    'Zip',
                    '',
                    'StateName',
                    'Country ISO',
                ],
            ],
            'Address Without state' => [
                'Street',
                'City',
                'Zip',
                'Address 1',
                'Address 2',
                null,
                'Country ISO',
                // Expected
                [
                    'Street',
                    'City',
                    'Zip',
                    'Address 1',
                    '',
                    'Country ISO',
                ],
            ],
            'Address Without country iso' => [
                'Street',
                'City',
                'Zip',
                'Address 1',
                'Address 2',
                'StateName',
                null,
                // Expected
                [
                    'Street',
                    'City',
                    'Zip',
                    'Address 1',
                    'StateName',
                    '',
                ],
            ],
        ];
    }

    public function formatPriceCheckoutProvider(): array
    {
        return [
            'Test full value' => [
                'expected' => 124,
                'price' => 124,
                'currency' => Currency::$BIF,
            ],
            'Test full value with many decimal number' => [
                'expected' => 124,
                'price' => 123.23432,
                'currency' => Currency::$BIF,
            ],
            'Test x1000 float value' => [
                'expected' => 1000,
                'price' => 1,
                'currency' => Currency::$BHD,
            ],
            'Test x1000 with float value' => [
                'expected' => 1250,
                'price' => 1.25,
                'currency' => Currency::$IQD,
            ],
            'Test x1000 with many decimal number float value' => [
                'expected' => 124260,
                'price' => 124.2545435535,
                'currency' => Currency::$JOD,
            ],
            'Test x100 float value' => [
                'expected' => 100,
                'price' => 1,
                'currency' => 'other',
            ],
            'Test x100 with float value' => [
                'expected' => 125,
                'price' => 1.25,
                'currency' => 'other',
            ],
            'Test x100 with many decimal number float value' => [
                'expected' => 12426,
                'price' => 124.2545435535,
                'currency' => 'other',
            ],
            'Test x100 last `00` float value' => [
                'expected' => 12400,
                'price' => 124,
                'currency' => Currency::$CLP,
            ],
            'Test x100 last `00` with many decimal number float value' => [
                'expected' => 12500,
                'price' => 124.2545435535,
                'currency' => Currency::$CLP,
            ],
        ];
    }

    protected function testBuildShipDetail(CustomerAddressEntity $customerAddress): void
    {
        $checkoutShipDetail = CheckoutComUtil::buildShipDetail($customerAddress);
        $expectedAddress = CheckoutComUtil::buildAddress($customerAddress);

        static::assertInstanceOf(ShippingDetails::class, $checkoutShipDetail);
        static::assertInstanceOf(Address::class, $checkoutShipDetail->address);
        static::assertEquals($expectedAddress, $checkoutShipDetail->address);
    }
}
