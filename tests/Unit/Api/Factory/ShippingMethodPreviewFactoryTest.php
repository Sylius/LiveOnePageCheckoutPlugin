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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Shipping\Calculator\CalculatorInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShippingMethodPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShippingMethodPreviewFactoryInterface;

final class ShippingMethodPreviewFactoryTest extends TestCase
{
    private ServiceRegistryInterface&MockObject $shippingCalculators;

    private ShippingMethodPreviewFactory $factory;

    protected function setUp(): void
    {
        $this->shippingCalculators = $this->createMock(ServiceRegistryInterface::class);
        $this->factory = new ShippingMethodPreviewFactory($this->shippingCalculators);
    }

    public function testImplementsShippingMethodPreviewFactoryInterface(): void
    {
        self::assertInstanceOf(ShippingMethodPreviewFactoryInterface::class, $this->factory);
    }

    public function testCreatesShippingMethodPreviewWithCalculatedCost(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->method('getCode')->willReturn('DHL');
        $shippingMethod->method('getName')->willReturn('DHL Express');
        $shippingMethod->method('getDescription')->willReturn('Fast delivery');
        $shippingMethod->method('getCalculator')->willReturn('flat_rate');
        $shippingMethod->method('getConfiguration')->willReturn(['amount' => 500]);

        $calculator = $this->createMock(CalculatorInterface::class);
        $calculator->expects(self::once())
            ->method('calculate')
            ->with($shipment, ['amount' => 500])
            ->willReturn(500);

        $this->shippingCalculators->expects(self::once())
            ->method('get')
            ->with('flat_rate')
            ->willReturn($calculator);

        $result = $this->factory->create($shipment, $shippingMethod);

        self::assertSame('DHL', $result->code);
        self::assertSame('DHL Express', $result->name);
        self::assertSame('Fast delivery', $result->description);
        self::assertSame(500, $result->cost);
    }
}
