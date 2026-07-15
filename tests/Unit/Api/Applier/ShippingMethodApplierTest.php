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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Applier;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\ShippingMethodApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final class ShippingMethodApplierTest extends TestCase
{
    private ShippingMethodsResolverInterface&MockObject $shippingMethodsResolver;

    private ShippingMethodApplier $applier;

    protected function setUp(): void
    {
        $this->shippingMethodsResolver = $this->createMock(ShippingMethodsResolverInterface::class);
        $this->applier = new ShippingMethodApplier($this->shippingMethodsResolver);
    }

    public function testImplementsCheckoutApplierInterface(): void
    {
        self::assertInstanceOf(CheckoutApplierInterface::class, $this->applier);
    }

    public function testAppliesSupportedShippingMethodByCode(): void
    {
        $method = $this->createMock(ShippingMethodInterface::class);
        $method->method('getCode')->willReturn('UPS');

        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->expects(self::once())->method('setMethod')->with($method);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->with($shipment)
            ->willReturn([$method]);

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => 'UPS']]);

        $this->applier->apply($order, $command);
    }

    public function testSetsNullWhenRequestedMethodIsNotSupported(): void
    {
        $supportedMethod = $this->createMock(ShippingMethodInterface::class);
        $supportedMethod->method('getCode')->willReturn('DHL');

        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->expects(self::once())->method('setMethod')->with(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->with($shipment)
            ->willReturn([$supportedMethod]);

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => 'UPS']]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenMethodCodeIsNull(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->expects(self::never())->method('setMethod');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => null]]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenMethodCodeIsEmpty(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->expects(self::never())->method('setMethod');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => '']]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenNoShipmentAtIndex(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([]));

        $this->shippingMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => 'UPS']]);

        $this->applier->apply($order, $command);
    }

    public function testAppliesMethodPerShipmentByPosition(): void
    {
        $ups = $this->createMock(ShippingMethodInterface::class);
        $ups->method('getCode')->willReturn('UPS');
        $dhl = $this->createMock(ShippingMethodInterface::class);
        $dhl->method('getCode')->willReturn('DHL');

        $firstShipment = $this->createMock(ShipmentInterface::class);
        $firstShipment->expects(self::once())->method('setMethod')->with($ups);
        $secondShipment = $this->createMock(ShipmentInterface::class);
        $secondShipment->expects(self::once())->method('setMethod')->with($dhl);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$firstShipment, $secondShipment]));

        $this->shippingMethodsResolver
            ->method('getSupportedMethods')
            ->willReturnMap([
                [$firstShipment, [$ups]],
                [$secondShipment, [$dhl]],
            ]);

        $command = new Checkout(
            orderTokenValue: 'token123',
            shipments: [['methodCode' => 'UPS'], ['methodCode' => 'DHL']],
        );

        $this->applier->apply($order, $command);
    }

    public function testDoesNothingWhenNoShipmentsRequested(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([]));

        $this->shippingMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $command = new Checkout(orderTokenValue: 'token123', shipments: []);

        $this->applier->apply($order, $command);
    }
}
