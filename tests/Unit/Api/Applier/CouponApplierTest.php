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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Promotion\Model\PromotionCouponInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CouponApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final class CouponApplierTest extends TestCase
{
    /** @var PromotionCouponRepositoryInterface<PromotionCouponInterface>&MockObject */
    private PromotionCouponRepositoryInterface&MockObject $promotionCouponRepository;

    private CouponApplier $applier;

    protected function setUp(): void
    {
        $this->promotionCouponRepository = $this->createMock(PromotionCouponRepositoryInterface::class);
        $this->applier = new CouponApplier($this->promotionCouponRepository);
    }

    public function testImplementsCheckoutApplierInterface(): void
    {
        self::assertInstanceOf(CheckoutApplierInterface::class, $this->applier);
    }

    public function testAppliesCouponByCode(): void
    {
        $coupon = $this->createMock(PromotionCouponInterface::class);
        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::once())
            ->method('setPromotionCoupon')
            ->with($coupon);

        $command = new Checkout(
            orderTokenValue: 'token123',
            couponCode: 'SUMMER_SALE',
        );

        $this->promotionCouponRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'SUMMER_SALE'])
            ->willReturn($coupon);

        $this->applier->apply($order, $command);
    }

    public function testSetsNullWhenCouponCodeIsNull(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::once())
            ->method('setPromotionCoupon')
            ->with(null);

        $command = new Checkout(
            orderTokenValue: 'token123',
            couponCode: null,
        );

        $this->promotionCouponRepository->expects(self::never())->method('findOneBy');

        $this->applier->apply($order, $command);
    }

    public function testSetsNullWhenCouponCodeIsEmpty(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::once())
            ->method('setPromotionCoupon')
            ->with(null);

        $command = new Checkout(
            orderTokenValue: 'token123',
            couponCode: '',
        );

        $this->promotionCouponRepository->expects(self::never())->method('findOneBy');

        $this->applier->apply($order, $command);
    }

    public function testSetsNullWhenCouponNotFound(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::once())
            ->method('setPromotionCoupon')
            ->with(null);

        $command = new Checkout(
            orderTokenValue: 'token123',
            couponCode: 'NONEXISTENT',
        );

        $this->promotionCouponRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'NONEXISTENT'])
            ->willReturn(null);

        $this->applier->apply($order, $command);
    }

    public function testSetsNullByDefault(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::once())
            ->method('setPromotionCoupon')
            ->with(null);

        $command = new Checkout(orderTokenValue: 'token123');

        $this->promotionCouponRepository->expects(self::never())->method('findOneBy');

        $this->applier->apply($order, $command);
    }
}
