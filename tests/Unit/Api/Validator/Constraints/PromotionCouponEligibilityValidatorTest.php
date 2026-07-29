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
use Sylius\Bundle\CoreBundle\Resolver\CustomerResolverInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PromotionCouponEligibility;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PromotionCouponEligibilityValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PromotionCouponEligibilityValidator>
 */
final class PromotionCouponEligibilityValidatorTest extends ConstraintValidatorTestCase
{
    /** @var PromotionCouponRepositoryInterface<PromotionCouponInterface>&MockObject */
    private PromotionCouponRepositoryInterface&MockObject $promotionCouponRepository;

    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    private AppliedCouponEligibilityCheckerInterface&MockObject $appliedCouponEligibilityChecker;

    private CustomerResolverInterface&MockObject $customerResolver;

    protected function setUp(): void
    {
        $this->promotionCouponRepository = $this->createMock(PromotionCouponRepositoryInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->appliedCouponEligibilityChecker = $this->createMock(AppliedCouponEligibilityCheckerInterface::class);
        $this->customerResolver = $this->createMock(CustomerResolverInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new PromotionCouponEligibilityValidator(
            $this->promotionCouponRepository,
            $this->orderRepository,
            $this->appliedCouponEligibilityChecker,
            $this->customerResolver,
        );
    }

    public function testNoViolationWhenCouponCodeIsNull(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', couponCode: null);

        $this->promotionCouponRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new PromotionCouponEligibility());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenCouponCodeIsEmpty(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', couponCode: '');

        $this->promotionCouponRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new PromotionCouponEligibility());

        $this->assertNoViolation();
    }

    public function testViolationWhenCouponNotFound(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', couponCode: 'NONEXISTENT');

        $this->promotionCouponRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'NONEXISTENT'])
            ->willReturn(null);

        $this->orderRepository->expects(self::never())->method('findCartByTokenValue');

        $this->validator->validate($command, new PromotionCouponEligibility());

        $this->buildViolation('sylius.promotion_coupon.is_invalid')
            ->atPath('property.path.couponCode')
            ->assertRaised();
    }

    public function testNoViolationWhenCartCannotBeFound(): void
    {
        $coupon = $this->createMock(PromotionCouponInterface::class);
        $command = new Checkout(orderTokenValue: 'token123', couponCode: 'SUMMER_SALE');

        $this->promotionCouponRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'SUMMER_SALE'])
            ->willReturn($coupon);

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn(null);

        $this->appliedCouponEligibilityChecker->expects(self::never())->method('isEligible');

        $this->validator->validate($command, new PromotionCouponEligibility());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenCouponIsEligible(): void
    {
        $coupon = $this->createMock(PromotionCouponInterface::class);
        $cart = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token123', couponCode: 'SUMMER_SALE');

        $this->promotionCouponRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'SUMMER_SALE'])
            ->willReturn($coupon);

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($cart);

        $cart->expects(self::once())->method('setPromotionCoupon')->with($coupon);

        $this->appliedCouponEligibilityChecker->expects(self::once())
            ->method('isEligible')
            ->with($coupon, $cart)
            ->willReturn(true);

        $this->validator->validate($command, new PromotionCouponEligibility());

        $this->assertNoViolation();
    }

    public function testViolationWhenCouponIsNotEligible(): void
    {
        $coupon = $this->createMock(PromotionCouponInterface::class);
        $cart = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token123', couponCode: 'SUMMER_SALE');

        $this->promotionCouponRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'SUMMER_SALE'])
            ->willReturn($coupon);

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($cart);

        $cart->expects(self::once())->method('setPromotionCoupon')->with($coupon);

        $this->appliedCouponEligibilityChecker->expects(self::once())
            ->method('isEligible')
            ->with($coupon, $cart)
            ->willReturn(false);

        $this->validator->validate($command, new PromotionCouponEligibility());

        $this->buildViolation('sylius.promotion_coupon.is_invalid')
            ->atPath('property.path.couponCode')
            ->assertRaised();
    }

    public function testResolvesAndSetsCustomerWhenEmailIsProvidedBeforeCheckingEligibility(): void
    {
        $coupon = $this->createMock(PromotionCouponInterface::class);
        $cart = $this->createMock(OrderInterface::class);
        $customer = $this->createMock(CustomerInterface::class);
        $command = new Checkout(orderTokenValue: 'token123', email: 'shop@example.com', couponCode: 'SUMMER_SALE');

        $this->promotionCouponRepository->method('findOneBy')->willReturn($coupon);
        $this->orderRepository->method('findCartByTokenValue')->willReturn($cart);

        $this->customerResolver->expects(self::once())
            ->method('resolve')
            ->with('shop@example.com')
            ->willReturn($customer)
        ;
        $cart->expects(self::once())->method('setCustomer')->with($customer);

        $this->appliedCouponEligibilityChecker->method('isEligible')->willReturn(true);

        $this->validator->validate($command, new PromotionCouponEligibility());

        $this->assertNoViolation();
    }

    public function testDoesNotResolveCustomerWhenEmailIsNotProvided(): void
    {
        $coupon = $this->createMock(PromotionCouponInterface::class);
        $cart = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token123', couponCode: 'SUMMER_SALE');

        $this->promotionCouponRepository->method('findOneBy')->willReturn($coupon);
        $this->orderRepository->method('findCartByTokenValue')->willReturn($cart);

        $this->customerResolver->expects(self::never())->method('resolve');
        $cart->expects(self::never())->method('setCustomer');

        $this->appliedCouponEligibilityChecker->method('isEligible')->willReturn(true);

        $this->validator->validate($command, new PromotionCouponEligibility());

        $this->assertNoViolation();
    }
}
