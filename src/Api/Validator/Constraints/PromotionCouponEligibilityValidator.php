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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class PromotionCouponEligibilityValidator extends ConstraintValidator
{
    public function __construct(
        private AppliedCouponEligibilityCheckerInterface $appliedCouponEligibilityChecker,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, OrderInterface::class);

        /** @var PromotionCouponEligibility $constraint */
        Assert::isInstanceOf($constraint, PromotionCouponEligibility::class);

        $promotionCoupon = $value->getPromotionCoupon();

        if (!$promotionCoupon instanceof PromotionCouponInterface) {
            return;
        }

        if (!$this->appliedCouponEligibilityChecker->isEligible($promotionCoupon, $value)) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('couponCode')
                ->addViolation()
            ;
        }
    }
}
