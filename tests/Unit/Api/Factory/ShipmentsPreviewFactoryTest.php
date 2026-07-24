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
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\ShippingMethodPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShipmentsPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShipmentsPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShippingMethodPreviewFactoryInterface;

final class ShipmentsPreviewFactoryTest extends TestCase
{
    private ShippingMethodsResolverInterface&MockObject $shippingMethodsResolver;

    private ShippingMethodPreviewFactoryInterface&MockObject $shippingMethodPreviewFactory;

    private ShipmentsPreviewFactory $factory;

    protected function setUp(): void
    {
        $this->shippingMethodsResolver = $this->createMock(ShippingMethodsResolverInterface::class);
        $this->shippingMethodPreviewFactory = $this->createMock(ShippingMethodPreviewFactoryInterface::class);
        $this->factory = new ShipmentsPreviewFactory($this->shippingMethodsResolver, $this->shippingMethodPreviewFactory);
    }

    public function testImplementsShipmentsPreviewFactoryInterface(): void
    {
        self::assertInstanceOf(ShipmentsPreviewFactoryInterface::class, $this->factory);
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

        $preview1 = new ShippingMethodPreview('DHL', 'DHL name', 'DHL description', 500);
        $preview2 = new ShippingMethodPreview('DPD', 'DPD name', 'DPD description', 700);
        $preview3 = new ShippingMethodPreview('DHL', 'DHL name', 'DHL description', 500);
        $preview4 = new ShippingMethodPreview('DPD', 'DPD name', 'DPD description', 700);

        $this->shippingMethodPreviewFactory->method('create')
            ->willReturnMap([
                [$shipment1, $supportedMethod1, $preview1],
                [$shipment1, $supportedMethod2, $preview2],
                [$shipment2, $supportedMethod3, $preview3],
                [$shipment2, $supportedMethod4, $preview4],
            ]);

        $result = $this->factory->create($order);

        self::assertCount(2, $result);
        self::assertInstanceOf(ShipmentPreview::class, $result[0]);
        self::assertInstanceOf(ShipmentPreview::class, $result[1]);
        self::assertSame('FEDEX', $result[0]->selectedMethodCode);
        self::assertSame('UPS', $result[1]->selectedMethodCode);
        self::assertSame([$preview1, $preview2], $result[0]->availableMethods);
        self::assertSame([$preview3, $preview4], $result[1]->availableMethods);
    }

    public function testCreatesShipmentPreviewWhenShipmentHasNoMethod(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->method('getMethod')->willReturn(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $supportedMethod = $this->createMock(ShippingMethodInterface::class);
        $preview = new ShippingMethodPreview('DHL', 'DHL name', 'DHL description', 500);

        $this->shippingMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->with($shipment)
            ->willReturn([$supportedMethod]);

        $this->shippingMethodPreviewFactory->method('create')->with($shipment, $supportedMethod)->willReturn($preview);

        $result = $this->factory->create($order);

        self::assertCount(1, $result);
        self::assertNull($result[0]->selectedMethodCode);
        self::assertSame([$preview], $result[0]->availableMethods);
    }

    public function testHandlesEmptyShipments(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([]));

        $this->shippingMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $result = $this->factory->create($order);

        self::assertCount(0, $result);
    }
}
