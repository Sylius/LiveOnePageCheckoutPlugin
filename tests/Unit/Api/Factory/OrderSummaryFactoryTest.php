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
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Promotion\Model\PromotionCouponInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\OrderSummary;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\OrderSummaryFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\OrderSummaryFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\OrderAndItemPromotionTotalAwareInterface;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Order;

final class OrderSummaryFactoryTest extends TestCase
{
    private OrderSummaryFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new OrderSummaryFactory();
    }

    private function mockOrder(): MockObject
    {
        return $this->createMock(Order::class);
    }

    public function testImplementsOrderSummaryFactoryInterface(): void
    {
        self::assertInstanceOf(OrderSummaryFactoryInterface::class, $this->factory);
    }

    public function testCreatesOrderSummaryWithAllData(): void
    {
        $coupon = $this->createMock(PromotionCouponInterface::class);
        $coupon->method('getCode')->willReturn('SUMMER_SALE');

        /** @var OrderInterface&OrderAndItemPromotionTotalAwareInterface&MockObject $order */
        $order = $this->mockOrder();
        $order->method('getItemsTotal')->willReturn(5000);
        $order->method('getItemsSubtotal')->willReturn(5000);
        $order->method('getTotal')->willReturn(6100);
        $order->method('getTaxTotal')->willReturn(600);
        $order->method('getShippingTaxTotal')->willReturn(100);
        $order->method('getTaxExcludedTotal')->willReturn(0);
        $order->method('getTaxIncludedTotal')->willReturn(0);
        $order->method('getShippingTotal')->willReturn(1000);
        $order->method('getOrderPromotionTotal')->willReturn(-500);
        $order->method('getOrderAndItemPromotionTotal')->willReturn(-700);
        $order->method('getShippingPromotionTotal')->willReturn(-200);
        $order->method('getPromotionCoupon')->willReturn($coupon);

        $result = $this->factory->create($order);

        self::assertInstanceOf(OrderSummary::class, $result);
        self::assertSame(5000, $result->itemsTotal);
        self::assertSame(5000, $result->itemsSubtotal);
        self::assertSame(6100, $result->total);
        self::assertSame(600, $result->taxTotal);
        self::assertSame(100, $result->shippingTaxTotal);
        self::assertSame(0, $result->taxExcludedTotal);
        self::assertSame(0, $result->taxIncludedTotal);
        self::assertSame(1000, $result->shippingTotal);
        self::assertSame(-500, $result->orderPromotionTotal);
        self::assertSame(-700, $result->orderAndItemPromotionTotal);
        self::assertSame(-200, $result->shippingPromotionTotal);
        self::assertSame('SUMMER_SALE', $result->promotionCoupon);
    }

    public function testCreatesOrderSummaryWithoutPromotionCoupon(): void
    {
        /** @var OrderInterface&OrderAndItemPromotionTotalAwareInterface&MockObject $order */
        $order = $this->mockOrder();
        $order->method('getItemsTotal')->willReturn(3000);
        $order->method('getItemsSubtotal')->willReturn(3000);
        $order->method('getTotal')->willReturn(3850);
        $order->method('getTaxTotal')->willReturn(350);
        $order->method('getShippingTaxTotal')->willReturn(0);
        $order->method('getTaxExcludedTotal')->willReturn(0);
        $order->method('getTaxIncludedTotal')->willReturn(0);
        $order->method('getShippingTotal')->willReturn(500);
        $order->method('getOrderPromotionTotal')->willReturn(0);
        $order->method('getOrderAndItemPromotionTotal')->willReturn(0);
        $order->method('getShippingPromotionTotal')->willReturn(0);
        $order->method('getPromotionCoupon')->willReturn(null);

        $result = $this->factory->create($order);

        self::assertNull($result->promotionCoupon);
        self::assertSame(3000, $result->itemsTotal);
        self::assertSame(500, $result->shippingTotal);
        self::assertSame(350, $result->taxTotal);
        self::assertSame(0, $result->orderPromotionTotal);
        self::assertSame(3850, $result->total);
    }
}
