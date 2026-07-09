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

namespace Sylius\LiveOnePageCheckoutPlugin\Validator\Constraints;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class OrderPaymentMethodSelectedValidator extends ConstraintValidator
{
    public function __construct(private readonly CheckoutEnabledCheckerInterface $checkoutEnabledChecker)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, OrderInterface::class);
        Assert::isInstanceOf($constraint, OrderPaymentMethodSelected::class);

        /** @var ChannelInterface $channel */
        $channel = $value->getChannel();

        if (!$this->checkoutEnabledChecker->isEnabled($channel)) {
            return;
        }

        foreach ($value->getPayments() as $payment) {
            if (PaymentInterface::STATE_CART === $payment->getState()) {
                return;
            }
        }

        $this->context
            ->buildViolation($constraint->message)
            ->atPath('payments')
            ->addViolation()
        ;
    }
}
