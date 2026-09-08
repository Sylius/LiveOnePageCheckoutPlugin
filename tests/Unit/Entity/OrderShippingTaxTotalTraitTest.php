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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Adjustment;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\Shipment;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Order;

final class OrderShippingTaxTotalTraitTest extends TestCase
{
    public function testReturnsZeroWhenThereAreNoShipments(): void
    {
        self::assertSame(0, (new Order())->getShippingTaxTotal());
    }

    public function testReturnsZeroWhenShipmentCarriesNoTaxAdjustment(): void
    {
        $order = new Order();
        $shipment = $this->addShipment($order);
        $shipment->addAdjustment($this->adjustment(AdjustmentInterface::SHIPPING_ADJUSTMENT, 500));

        self::assertSame(0, $order->getShippingTaxTotal());
    }

    public function testIgnoresTheShippingCostAdjustment(): void
    {
        $order = new Order();
        $shipment = $this->addShipment($order);
        $shipment->addAdjustment($this->adjustment(AdjustmentInterface::SHIPPING_ADJUSTMENT, 500));
        $shipment->addAdjustment($this->adjustment(AdjustmentInterface::TAX_ADJUSTMENT, 50));

        self::assertSame(50, $order->getShippingTaxTotal());
    }

    public function testCountsTaxIncludedInPrice(): void
    {
        $order = new Order();
        $shipment = $this->addShipment($order);
        $shipment->addAdjustment($this->adjustment(AdjustmentInterface::TAX_ADJUSTMENT, 50, neutral: true));

        self::assertSame(50, $order->getShippingTaxTotal());
    }

    public function testSumsTaxAcrossShipments(): void
    {
        $order = new Order();

        $firstShipment = $this->addShipment($order);
        $firstShipment->addAdjustment($this->adjustment(AdjustmentInterface::TAX_ADJUSTMENT, 50));

        $secondShipment = $this->addShipment($order);
        $secondShipment->addAdjustment($this->adjustment(AdjustmentInterface::TAX_ADJUSTMENT, 30));

        self::assertSame(80, $order->getShippingTaxTotal());
    }

    private function addShipment(Order $order): Shipment
    {
        $shipment = new Shipment();
        $order->addShipment($shipment);

        return $shipment;
    }

    private function adjustment(string $type, int $amount, bool $neutral = false): Adjustment
    {
        $adjustment = new Adjustment();
        $adjustment->setType($type);
        $adjustment->setAmount($amount);
        $adjustment->setNeutral($neutral);

        return $adjustment;
    }
}
