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
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Abstraction\StateMachine\TransitionInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\CheckoutCompletion;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\CheckoutCompletionValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CheckoutCompletionValidator>
 */
final class CheckoutCompletionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    private StateMachineInterface&MockObject $stateMachine;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->stateMachine = $this->createMock(StateMachineInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new CheckoutCompletionValidator($this->orderRepository, $this->stateMachine);
    }

    public function testNoViolationWhenOrderNotFound(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');

        $this->orderRepository->method('findCartByTokenValue')->willReturn(null);

        $this->validator->validate($command, new CheckoutCompletion());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenCompleteTransitionIsPossible(): void
    {
        $order = $this->createMock(OrderInterface::class);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->stateMachine
            ->method('can')
            ->with($order, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_COMPLETE)
            ->willReturn(true)
        ;

        $command = new Checkout(orderTokenValue: 'token123');
        $this->validator->validate($command, new CheckoutCompletion());

        $this->assertNoViolation();
    }

    public function testViolationWhenCompleteTransitionIsNotPossible(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCheckoutState')->willReturn('addressed');

        $skipShipping = $this->createMock(TransitionInterface::class);
        $skipShipping->method('getName')->willReturn('skip_shipping');

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->stateMachine->method('can')->willReturn(false);
        $this->stateMachine
            ->method('getEnabledTransitions')
            ->with($order, OrderCheckoutTransitions::GRAPH)
            ->willReturn([$skipShipping])
        ;

        $command = new Checkout(orderTokenValue: 'token123');
        $this->validator->validate($command, new CheckoutCompletion());

        $this->buildViolation('sylius.order.invalid_state_transition')
            ->setParameter('%currentState%', 'addressed')
            ->setParameter('%possibleTransitions%', 'skip_shipping')
            ->assertRaised()
        ;
    }
}
