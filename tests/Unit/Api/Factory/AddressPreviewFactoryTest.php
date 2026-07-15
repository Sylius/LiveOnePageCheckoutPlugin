<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Factory;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\AddressPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\AddressPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\AddressPreviewFactoryInterface;

final class AddressPreviewFactoryTest extends TestCase
{
    private AddressPreviewFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new AddressPreviewFactory();
    }

    public function testImplementsAddressPreviewFactoryInterface(): void
    {
        self::assertInstanceOf(AddressPreviewFactoryInterface::class, $this->factory);
    }

    public function testReturnsNullForNullAddress(): void
    {
        $result = $this->factory->create(null);

        self::assertNull($result);
    }

    public function testCreatesAddressPreviewWithAllFields(): void
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getFirstName')->willReturn('John');
        $address->method('getLastName')->willReturn('Doe');
        $address->method('getCompany')->willReturn('Acme Inc');
        $address->method('getStreet')->willReturn('123 Main St');
        $address->method('getCity')->willReturn('New York');
        $address->method('getPostcode')->willReturn('10001');
        $address->method('getCountryCode')->willReturn('US');
        $address->method('getProvinceCode')->willReturn('NY');
        $address->method('getProvinceName')->willReturn('New York');
        $address->method('getPhoneNumber')->willReturn('555-1234');

        $result = $this->factory->create($address);

        self::assertInstanceOf(AddressPreview::class, $result);
        self::assertSame('John', $result->firstName);
        self::assertSame('Doe', $result->lastName);
        self::assertSame('Acme Inc', $result->company);
        self::assertSame('123 Main St', $result->street);
        self::assertSame('New York', $result->city);
        self::assertSame('10001', $result->postcode);
        self::assertSame('US', $result->countryCode);
        self::assertSame('NY', $result->provinceCode);
        self::assertSame('New York', $result->provinceName);
        self::assertSame('555-1234', $result->phoneNumber);
    }

    public function testCreatesAddressPreviewWithNullFields(): void
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getFirstName')->willReturn(null);
        $address->method('getLastName')->willReturn(null);
        $address->method('getCompany')->willReturn(null);
        $address->method('getStreet')->willReturn(null);
        $address->method('getCity')->willReturn(null);
        $address->method('getPostcode')->willReturn(null);
        $address->method('getCountryCode')->willReturn(null);
        $address->method('getProvinceCode')->willReturn(null);
        $address->method('getProvinceName')->willReturn(null);
        $address->method('getPhoneNumber')->willReturn(null);

        $result = $this->factory->create($address);

        self::assertInstanceOf(AddressPreview::class, $result);
        self::assertNull($result->firstName);
        self::assertNull($result->lastName);
        self::assertNull($result->company);
        self::assertNull($result->street);
        self::assertNull($result->city);
        self::assertNull($result->postcode);
        self::assertNull($result->countryCode);
        self::assertNull($result->provinceCode);
        self::assertNull($result->provinceName);
        self::assertNull($result->phoneNumber);
    }

    public function testCreatesAddressPreviewWithPartialFields(): void
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getFirstName')->willReturn('Jane');
        $address->method('getLastName')->willReturn('Smith');
        $address->method('getCompany')->willReturn(null);
        $address->method('getStreet')->willReturn('456 Oak Ave');
        $address->method('getCity')->willReturn('Los Angeles');
        $address->method('getPostcode')->willReturn('90001');
        $address->method('getCountryCode')->willReturn('US');
        $address->method('getProvinceCode')->willReturn(null);
        $address->method('getProvinceName')->willReturn(null);
        $address->method('getPhoneNumber')->willReturn('555-5678');

        $result = $this->factory->create($address);

        self::assertInstanceOf(AddressPreview::class, $result);
        self::assertSame('Jane', $result->firstName);
        self::assertSame('Smith', $result->lastName);
        self::assertNull($result->company);
        self::assertSame('456 Oak Ave', $result->street);
        self::assertSame('Los Angeles', $result->city);
        self::assertSame('90001', $result->postcode);
        self::assertSame('US', $result->countryCode);
        self::assertNull($result->provinceCode);
        self::assertNull($result->provinceName);
        self::assertSame('555-5678', $result->phoneNumber);
    }
}
