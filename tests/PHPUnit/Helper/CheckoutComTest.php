<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Helper;

use Checkout\Common\Address;
use Checkout\Common\Currency;
use Checkout\Common\CustomerRequest;
use Checkout\Payments\ShippingDetails;
use CheckoutCom\Shopware6\Helper\CheckoutComUtil;
use CheckoutCom\Shopware6\Struct\DirectPay\Cart\DirectPayCartStruct;
use CheckoutCom\Shopware6\Struct\LineItemTotalPrice;
use CheckoutCom\Shopware6\Tests\Traits\OrderTrait;
use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

class CheckoutComTest extends TestCase
{
    use OrderTrait;

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

    /**
     * @dataProvider buildReferenceProvider
     */
    public function testBuildReference(?string $orderNumber): void
    {
        $orderId = 'foo';
        $order = new OrderEntity();
        $order->setId($orderId);

        if ($orderNumber === null) {
            static::expectException(Exception::class);
        } else {
            $order->setOrderNumber($orderNumber);
        }

        $actual = CheckoutComUtil::buildReference($order);
        static::assertSame(sprintf('ord_%s_id_%s', $orderNumber, $orderId), $actual);
    }

    public function testBuildCustomer(): void
    {
        $firstName = 'FirstName';
        $lastName = 'LastName';
        $email = 'email@test.com';
        $fullName = sprintf('%s %s', $firstName, $lastName);
        $orderCustomer = $this->getOrderCustomerEntity($firstName, $lastName, $email);
        $checkoutCustomer = CheckoutComUtil::buildCustomer($orderCustomer);

        static::assertInstanceOf(CustomerRequest::class, $checkoutCustomer);
        static::assertSame($fullName, $checkoutCustomer->name);
        static::assertSame($email, $checkoutCustomer->email);
    }

    public function testBuildLineItemTotalPrice(): void
    {
        $cart = new Cart('foo', 'bar');
        $lineItemTotal = CheckoutComUtil::buildLineItemTotalPrice($cart);

        static::assertInstanceOf(LineItemTotalPrice::class, $lineItemTotal);
    }

    /**
     * @dataProvider formatPriceCheckoutProvider
     */
    public function testFormatPriceCheckout(int $expected, float $price, string $currency): void
    {
        $result = CheckoutComUtil::formatPriceCheckout($price, $currency);
        static::assertSame($expected, $result);
    }

    /**
     * @dataProvider formatPriceShopwareProvider
     */
    public function testFormatPriceShopware(float $expected, int $price, string $currency): void
    {
        $result = CheckoutComUtil::formatPriceShopware($price, $currency);
        static::assertSame($expected, $result);
    }

    /**
     * @dataProvider buildDirectPayCartProvider
     */
    public function testBuildDirectPayCart(LineItemCollection $lineItems, DeliveryCollection $deliveries, float $taxAmount): void
    {
        $cart = $this->createMock(Cart::class);
        $cart->method('getLineItems')->willReturn($lineItems);
        $cart->method('getDeliveries')->willReturn($deliveries);

        $calculatedTaxCollection = $this->createConfiguredMock(CalculatedTaxCollection::class, [
            'getAmount' => $taxAmount,
        ]);

        $price = $this->createConfiguredMock(CartPrice::class, [
            'getCalculatedTaxes' => $calculatedTaxCollection,
        ]);

        $cart->method('getPrice')->willReturn($price);
        $cart->setLineItems($lineItems);
        $cart->setDeliveries($deliveries);

        $expect = CheckoutComUtil::buildDirectPayCart($cart);

        static::assertInstanceOf(DirectPayCartStruct::class, $expect);
    }

    public function buildReferenceProvider(): array
    {
        return [
            'Test could not find order number' => [
                null,
            ],
            'Test found order number' => [
                '1234',
            ],
        ];
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

    public function formatPriceShopwareProvider(): array
    {
        return [
            'Test full value' => [
                'expected' => 124,
                'price' => 124,
                'currency' => Currency::$BIF,
            ],
            'Test divide 1000 float value' => [
                'expected' => 1,
                'price' => 1000,
                'currency' => Currency::$BHD,
            ],
            'Test divide 1000 with float value' => [
                'expected' => 0.1,
                'price' => 100,
                'currency' => Currency::$IQD,
            ],
            'Test divide 100 float value' => [
                'expected' => 1,
                'price' => 100,
                'currency' => 'other',
            ],
            'Test divide 100 with float value' => [
                'expected' => 0.1,
                'price' => 10,
                'currency' => 'other',
            ],
        ];
    }

    public function buildDirectPayCartProvider(): array
    {
        return [
            'Test empty line items and empty delivery items and tax is 0' => [
                new LineItemCollection(),
                new DeliveryCollection(),
                0,
            ],
            'Test has line items but empty delivery items and tax is 0' => [
                new LineItemCollection([
                    new LineItem('foo1', 'bar'),
                    (new LineItem('foo2', 'bar'))
                        ->setPrice($this->createConfiguredMock(CalculatedPrice::class, [
                            'getUnitPrice' => 5.0,
                        ])),
                ]),
                new DeliveryCollection(),
                0.0,
            ],
            'Test empty line items but has delivery items and tax is 0' => [
                new LineItemCollection(),
                new DeliveryCollection([
                    $this->createConfiguredMock(Delivery::class, [
                        'getShippingCosts' => $this->createConfiguredMock(CalculatedPrice::class, [
                            'getUnitPrice' => 0.0,
                        ]),
                    ]),
                    $this->createConfiguredMock(Delivery::class, [
                        'getShippingCosts' => $this->createConfiguredMock(CalculatedPrice::class, [
                            'getUnitPrice' => 5.0,
                        ]),
                        'getShippingMethod' => $this->createConfiguredMock(ShippingMethodEntity::class, [
                            'getName' => 'foo',
                        ]),
                    ]),
                ]),
                0.0,
            ],
            'Test empty line and delivery items but tax is more than 0' => [
                new LineItemCollection(),
                new DeliveryCollection(),
                5.0,
            ],
        ];
    }

    protected function testBuildShipDetail(OrderAddressEntity $orderAddress): void
    {
        $checkoutShipDetail = CheckoutComUtil::buildShipDetail($orderAddress);
        $expectedAddress = CheckoutComUtil::buildAddress($orderAddress);

        static::assertInstanceOf(ShippingDetails::class, $checkoutShipDetail);
        static::assertInstanceOf(Address::class, $checkoutShipDetail->address);
        static::assertEquals($expectedAddress, $checkoutShipDetail->address);
    }
}
