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

use Sylius\Bundle\ApiBundle\Command\Checkout\ChoosePaymentMethod;
use Sylius\Bundle\ApiBundle\Command\Checkout\ChooseShippingMethod;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Command\Checkout\UpdateCart;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class StandardCheckoutStepBlockedValidator extends ConstraintValidator
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::true(
            $value instanceof UpdateCart ||
            $value instanceof ChooseShippingMethod ||
            $value instanceof ChoosePaymentMethod ||
            $value instanceof CompleteOrder,
        );

        /** @var UpdateCart|ChooseShippingMethod|ChoosePaymentMethod|CompleteOrder $value */
        Assert::isInstanceOf($constraint, StandardCheckoutStepBlocked::class);

        $order = $this->orderRepository->findCartByTokenValue($value->orderTokenValue);

        if (!$order instanceof OrderInterface) {
            return;
        }

        $channel = $order->getChannel();

        if (!$channel instanceof ChannelOnePageCheckoutAwareInterface) {
            return;
        }

        if (!$this->checkoutEnabledChecker->isEnabled($channel)) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }
}
