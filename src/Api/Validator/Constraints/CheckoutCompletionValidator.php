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

use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Abstraction\StateMachine\TransitionInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class CheckoutCompletionValidator extends ConstraintValidator
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, Checkout::class);

        Assert::isInstanceOf($constraint, CheckoutCompletion::class);

        $order = $this->orderRepository->findCartByTokenValue($value->orderTokenValue);

        if (!$order instanceof OrderInterface) {
            return;
        }

        if ($this->stateMachine->can($order, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_COMPLETE)) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->setParameter('%currentState%', (string) $order->getCheckoutState())
            ->setParameter('%possibleTransitions%', implode(
                ', ',
                array_map(
                    static fn (TransitionInterface $transition): string => $transition->getName(),
                    $this->stateMachine->getEnabledTransitions($order, OrderCheckoutTransitions::GRAPH),
                ),
            ))
            ->addViolation()
        ;
    }
}
