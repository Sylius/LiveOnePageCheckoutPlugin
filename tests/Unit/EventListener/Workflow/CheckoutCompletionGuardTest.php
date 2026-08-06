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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\EventListener\Workflow;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Sylius\LiveOnePageCheckoutPlugin\EventListener\Workflow\CheckoutCompletionGuard;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Channel;

final class CheckoutCompletionGuardTest extends TestCase
{
    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private CheckoutCompletionGuard $guard;

    protected function setUp(): void
    {
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);
        $this->guard = new CheckoutCompletionGuard($this->checkoutEnabledChecker);
    }

    public function testBlocksCartCompletionWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(false);

        $event = $this->createCartCompletionEvent($channel);

        ($this->guard)($event);

        self::assertTrue($event->isBlocked());
    }

    public function testDoesNotBlockCartCompletionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        $event = $this->createCartCompletionEvent($channel);

        ($this->guard)($event);

        self::assertFalse($event->isBlocked());
    }

    public function testBlocksStandardCompletionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        $event = $this->createStandardCompletionEvent($channel);

        ($this->guard)($event);

        self::assertTrue($event->isBlocked());
    }

    public function testDoesNotBlockStandardCompletionWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(false);

        $event = $this->createStandardCompletionEvent($channel);

        ($this->guard)($event);

        self::assertFalse($event->isBlocked());
    }

    public function testThrowsWhenSubjectIsNotAnOrder(): void
    {
        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $event = $this->createEvent(new \stdClass(), $this->cartCompletionTransition());

        $this->expectException(\InvalidArgumentException::class);

        ($this->guard)($event);
    }

    public function testThrowsWhenChannelIsNotSupported(): void
    {
        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn(null);

        $event = $this->createEvent($order, $this->cartCompletionTransition());

        $this->expectException(\InvalidArgumentException::class);

        ($this->guard)($event);
    }

    private function createCartCompletionEvent(?ChannelOnePageCheckoutAwareInterface $channel): GuardEvent
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        return $this->createEvent($order, $this->cartCompletionTransition());
    }

    private function createStandardCompletionEvent(?ChannelOnePageCheckoutAwareInterface $channel): GuardEvent
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        return $this->createEvent($order, $this->standardCompletionTransition());
    }

    private function cartCompletionTransition(): Transition
    {
        return new Transition(
            OrderCheckoutTransitions::TRANSITION_COMPLETE,
            OrderCheckoutStates::STATE_CART,
            OrderCheckoutStates::STATE_COMPLETED,
        );
    }

    private function standardCompletionTransition(): Transition
    {
        return new Transition(
            OrderCheckoutTransitions::TRANSITION_COMPLETE,
            OrderCheckoutStates::STATE_PAYMENT_SELECTED,
            OrderCheckoutStates::STATE_COMPLETED,
        );
    }

    private function createEvent(object $subject, Transition $transition): GuardEvent
    {
        return new GuardEvent($subject, new Marking(), $transition);
    }
}
