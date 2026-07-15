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

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\ShipmentPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShipmentPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShipmentPreviewFactoryInterface;

final class ShipmentPreviewFactoryTest extends TestCase
{
    private ShippingMethodsResolverInterface&MockObject $shippingMethodsResolver;

    private ShipmentPreviewFactory $factory;

    protected function setUp(): void
    {
        $this->shippingMethodsResolver = $this->createMock(ShippingMethodsResolverInterface::class);
        $this->factory = new ShipmentPreviewFactory($this->shippingMethodsResolver);
    }

    public function testImplementsShipmentPreviewFactoryInterface(): void
    {
        self::assertInstanceOf(ShipmentPreviewFactoryInterface::class, $this->factory);
    }

    public function testCreatesShipmentPreviewsForAllShipments(): void
    {
        $method1 = $this->createMock(ShippingMethodInterface::class);
        $method1->method('getCode')->willReturn('FEDEX');
        $shipment1 = $this->createMock(ShipmentInterface::class);
        $shipment1->method('getMethod')->willReturn($method1);

        $method2 = $this->createMock(ShippingMethodInterface::class);
        $method2->method('getCode')->willReturn('UPS');
        $shipment2 = $this->createMock(ShipmentInterface::class);
        $shipment2->method('getMethod')->willReturn($method2);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment1, $shipment2]));

        $supportedMethod1 = $this->createMock(ShippingMethodInterface::class);
        $supportedMethod2 = $this->createMock(ShippingMethodInterface::class);
        $supportedMethod3 = $this->createMock(ShippingMethodInterface::class);
        $supportedMethod4 = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodsResolver->method('getSupportedMethods')
            ->willReturnMap([
                [$shipment1, [$supportedMethod1, $supportedMethod2]],
                [$shipment2, [$supportedMethod3, $supportedMethod4]],
            ]);

        $result = $this->factory->create($order);

        self::assertCount(2, $result);
        self::assertInstanceOf(ShipmentPreview::class, $result[0]);
        self::assertInstanceOf(ShipmentPreview::class, $result[1]);
        self::assertSame('FEDEX', $result[0]->selectedMethodCode);
        self::assertSame('UPS', $result[1]->selectedMethodCode);
        self::assertSame([$supportedMethod1, $supportedMethod2], $result[0]->availableMethods);
        self::assertSame([$supportedMethod3, $supportedMethod4], $result[1]->availableMethods);
    }

    public function testCreatesShipmentPreviewWhenShipmentHasNoMethod(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->method('getMethod')->willReturn(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $supportedMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->with($shipment)
            ->willReturn([$supportedMethod]);

        $result = $this->factory->create($order);

        self::assertCount(1, $result);
        self::assertNull($result[0]->selectedMethodCode);
        self::assertSame([$supportedMethod], $result[0]->availableMethods);
    }

    public function testHandlesEmptyShipments(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([]));

        $this->shippingMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $result = $this->factory->create($order);

        self::assertCount(0, $result);
    }

    public function testArrayValuesResultsAreReturned(): void
    {
        $supportedMethod1 = $this->createMock(ShippingMethodInterface::class);
        $supportedMethod2 = $this->createMock(ShippingMethodInterface::class);

        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->method('getMethod')->willReturn(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->willReturn([$supportedMethod1, $supportedMethod2]);

        $result = $this->factory->create($order);

        self::assertSame([$supportedMethod1, $supportedMethod2], $result[0]->availableMethods);
    }
}
