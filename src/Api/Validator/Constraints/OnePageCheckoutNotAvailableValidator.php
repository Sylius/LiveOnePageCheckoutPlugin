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

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class OnePageCheckoutNotAvailableValidator extends ConstraintValidator
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, Checkout::class);

        Assert::isInstanceOf($constraint, OnePageCheckoutNotAvailable::class);

        $order = $this->orderRepository->findCartByTokenValue($value->orderTokenValue);

        if (!$order instanceof OrderInterface) {
            return;
        }

        $channel = $order->getChannel();
        Assert::isInstanceOf($channel, ChannelOnePageCheckoutAwareInterface::class);

        if (false === $this->checkoutEnabledChecker->isEnabled($channel)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
