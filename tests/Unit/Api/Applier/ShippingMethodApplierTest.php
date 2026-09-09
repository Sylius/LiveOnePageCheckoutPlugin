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
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\ShippingMethodApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final class ShippingMethodApplierTest extends TestCase
{
    /** @var ShippingMethodRepositoryInterface<ShippingMethodInterface>&MockObject */
    private ShippingMethodRepositoryInterface&MockObject $shippingMethodRepository;

    private ShippingMethodApplier $applier;

    protected function setUp(): void
    {
        $this->shippingMethodRepository = $this->createMock(ShippingMethodRepositoryInterface::class);
        $this->applier = new ShippingMethodApplier($this->shippingMethodRepository);
    }

    public function testImplementsCheckoutApplierInterface(): void
    {
        self::assertInstanceOf(CheckoutApplierInterface::class, $this->applier);
    }

    public function testAppliesExistingShippingMethodByCode(): void
    {
        $method = $this->createMock(ShippingMethodInterface::class);

        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->expects(self::once())->method('setMethod')->with($method);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'UPS'])
            ->willReturn($method);

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => 'UPS']]);

        $this->applier->apply($order, $command);
    }

    public function testAppliesMethodUnavailableForTheAddressAndLeavesItToValidation(): void
    {
        $method = $this->createMock(ShippingMethodInterface::class);

        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->expects(self::once())->method('setMethod')->with($method);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'DHL'])
            ->willReturn($method);

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => 'DHL']]);

        $this->applier->apply($order, $command);
    }

    public function testSetsNullWhenRequestedMethodDoesNotExist(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->expects(self::once())->method('setMethod')->with(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'BOGUS'])
            ->willReturn(null);

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => 'BOGUS']]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenMethodCodeIsNull(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->expects(self::never())->method('setMethod');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodRepository->expects(self::never())->method('findOneBy');

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => null]]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenMethodCodeIsEmpty(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $shipment->expects(self::never())->method('setMethod');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$shipment]));

        $this->shippingMethodRepository->expects(self::never())->method('findOneBy');

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => '']]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenNoShipmentAtIndex(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([]));

        $this->shippingMethodRepository->expects(self::never())->method('findOneBy');

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => 'UPS']]);

        $this->applier->apply($order, $command);
    }

    public function testAppliesMethodPerShipmentByPosition(): void
    {
        $ups = $this->createMock(ShippingMethodInterface::class);
        $dhl = $this->createMock(ShippingMethodInterface::class);

        $firstShipment = $this->createMock(ShipmentInterface::class);
        $firstShipment->expects(self::once())->method('setMethod')->with($ups);
        $secondShipment = $this->createMock(ShipmentInterface::class);
        $secondShipment->expects(self::once())->method('setMethod')->with($dhl);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShipments')->willReturn(new ArrayCollection([$firstShipment, $secondShipment]));

        $this->shippingMethodRepository
            ->method('findOneBy')
            ->willReturnMap([
                [['code' => 'UPS'], $ups],
                [['code' => 'DHL'], $dhl],
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

        $this->shippingMethodRepository->expects(self::never())->method('findOneBy');

        $command = new Checkout(orderTokenValue: 'token123', shipments: []);

        $this->applier->apply($order, $command);
    }
}
