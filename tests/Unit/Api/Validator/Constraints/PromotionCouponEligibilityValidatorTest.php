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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Validator\Constraints;

use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\ApiBundle\Checker\AppliedCouponEligibilityCheckerInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PromotionCouponEligibility;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PromotionCouponEligibilityValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PromotionCouponEligibilityValidator>
 */
final class PromotionCouponEligibilityValidatorTest extends ConstraintValidatorTestCase
{
    private AppliedCouponEligibilityCheckerInterface&MockObject $appliedCouponEligibilityChecker;

    protected function setUp(): void
    {
        $this->appliedCouponEligibilityChecker = $this->createMock(AppliedCouponEligibilityCheckerInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new PromotionCouponEligibilityValidator($this->appliedCouponEligibilityChecker);
    }

    public function testNoViolationWhenOrderHasNoPromotionCoupon(): void
    {
        $order = new Order();

        $this->appliedCouponEligibilityChecker->expects(self::never())->method('isEligible');

        $this->validator->validate($order, new PromotionCouponEligibility());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenCouponIsEligible(): void
    {
        $coupon = $this->createMock(PromotionCouponInterface::class);
        $order = new Order();
        $order->setPromotionCoupon($coupon);

        $this->appliedCouponEligibilityChecker->expects(self::once())
            ->method('isEligible')
            ->with($coupon, $order)
            ->willReturn(true);

        $this->validator->validate($order, new PromotionCouponEligibility());

        $this->assertNoViolation();
    }

    public function testViolationWhenCouponIsNotEligible(): void
    {
        $coupon = $this->createMock(PromotionCouponInterface::class);
        $order = new Order();
        $order->setPromotionCoupon($coupon);

        $this->appliedCouponEligibilityChecker->expects(self::once())
            ->method('isEligible')
            ->with($coupon, $order)
            ->willReturn(false);

        $this->validator->validate($order, new PromotionCouponEligibility());

        $this->buildViolation('sylius.promotion_coupon.is_invalid')
            ->atPath('property.path.couponCode')
            ->assertRaised();
    }
}
