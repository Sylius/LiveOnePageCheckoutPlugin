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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints;

use Sylius\Bundle\ApiBundle\Checker\AppliedCouponEligibilityCheckerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class PromotionCouponEligibilityValidator extends ConstraintValidator
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     * @param PromotionCouponRepositoryInterface<PromotionCouponInterface> $promotionCouponRepository
     */
    public function __construct(
        private PromotionCouponRepositoryInterface $promotionCouponRepository,
        private OrderRepositoryInterface $orderRepository,
        private AppliedCouponEligibilityCheckerInterface $appliedCouponEligibilityChecker,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, Checkout::class);

        /** @var PromotionCouponEligibility $constraint */
        Assert::isInstanceOf($constraint, PromotionCouponEligibility::class);

        if (null === $value->couponCode || '' === $value->couponCode) {
            return;
        }

        $promotionCoupon = $this->promotionCouponRepository->findOneBy(['code' => $value->couponCode]);

        if (!$promotionCoupon instanceof PromotionCouponInterface) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('couponCode')
                ->addViolation()
            ;

            return;
        }

        $cart = $this->orderRepository->findCartByTokenValue($value->orderTokenValue);

        if (!$cart instanceof OrderInterface) {
            return;
        }

        $cart->setPromotionCoupon($promotionCoupon);

        if (!$this->appliedCouponEligibilityChecker->isEligible($promotionCoupon, $cart)) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('couponCode')
                ->addViolation()
            ;
        }
    }
}
