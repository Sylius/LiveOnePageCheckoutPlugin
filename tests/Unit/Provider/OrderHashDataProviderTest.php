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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Model\PromotionInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\LiveOnePageCheckoutPlugin\Provider\OrderHashDataProvider;

final class OrderHashDataProviderTest extends TestCase
{
    private OrderHashDataProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new OrderHashDataProvider();
    }

    public function testCollectsOrderTotalAndSortedCodes(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getTotal')->willReturn(1500);
        $order->method('getPromotions')->willReturn(new ArrayCollection([
            $this->promotion('WINTER'),
            $this->promotion('CHRISTMAS'),
        ]));
        $order->method('getShipments')->willReturn(new ArrayCollection([
            $this->shipment('UPS'),
            $this->shipment('DHL'),
        ]));
        $order->method('getPayments')->willReturn(new ArrayCollection([
            $this->payment('CASH_ON_DELIVERY'),
            $this->payment('BANK_TRANSFER'),
        ]));

        $data = $this->provider->getData($order);

        self::assertSame([
            'total' => 1500,
            'promotions' => ['CHRISTMAS', 'WINTER'],
            'shipments' => ['DHL', 'UPS'],
            'payments' => ['BANK_TRANSFER', 'CASH_ON_DELIVERY'],
        ], $data);
    }

    public function testHandlesShipmentAndPaymentWithoutMethod(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getTotal')->willReturn(0);
        $order->method('getPromotions')->willReturn(new ArrayCollection([]));
        $order->method('getShipments')->willReturn(new ArrayCollection([$this->shipment(null)]));
        $order->method('getPayments')->willReturn(new ArrayCollection([$this->payment(null)]));

        $data = $this->provider->getData($order);

        self::assertSame([], $data['promotions']);
        self::assertSame([null], $data['shipments']);
        self::assertSame([null], $data['payments']);
    }

    private function promotion(string $code): PromotionInterface
    {
        $promotion = $this->createMock(PromotionInterface::class);
        $promotion->method('getCode')->willReturn($code);

        return $promotion;
    }

    private function shipment(?string $methodCode): ShipmentInterface
    {
        $shipment = $this->createMock(ShipmentInterface::class);

        if (null === $methodCode) {
            $shipment->method('getMethod')->willReturn(null);

            return $shipment;
        }

        $method = $this->createMock(ShippingMethodInterface::class);
        $method->method('getCode')->willReturn($methodCode);
        $shipment->method('getMethod')->willReturn($method);

        return $shipment;
    }

    private function payment(?string $methodCode): PaymentInterface
    {
        $payment = $this->createMock(PaymentInterface::class);

        if (null === $methodCode) {
            $payment->method('getMethod')->willReturn(null);

            return $payment;
        }

        $method = $this->createMock(PaymentMethodInterface::class);
        $method->method('getCode')->willReturn($methodCode);
        $payment->method('getMethod')->willReturn($method);

        return $payment;
    }
}
