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
use Sylius\LiveOnePageCheckoutPlugin\EventListener\Workflow\StandardCheckoutStepGuard;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Channel;

final class StandardCheckoutStepGuardTest extends TestCase
{
    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private StandardCheckoutStepGuard $guard;

    protected function setUp(): void
    {
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);
        $this->guard = new StandardCheckoutStepGuard($this->checkoutEnabledChecker);
    }

    public function testBlocksAddressTransitionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        $event = $this->createEvent($channel, $this->addressTransition());

        ($this->guard)($event);

        self::assertTrue($event->isBlocked());
    }

    public function testDoesNotBlockAddressTransitionWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(false);

        $event = $this->createEvent($channel, $this->addressTransition());

        ($this->guard)($event);

        self::assertFalse($event->isBlocked());
    }

    public function testBlocksSelectShippingTransitionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        $event = $this->createEvent($channel, $this->selectShippingTransition());

        ($this->guard)($event);

        self::assertTrue($event->isBlocked());
    }

    public function testBlocksSelectPaymentTransitionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        $event = $this->createEvent($channel, $this->selectPaymentTransition());

        ($this->guard)($event);

        self::assertTrue($event->isBlocked());
    }

    public function testThrowsWhenSubjectIsNotAnOrder(): void
    {
        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $event = new GuardEvent(new \stdClass(), new Marking(), $this->addressTransition());

        $this->expectException(\InvalidArgumentException::class);

        ($this->guard)($event);
    }

    public function testThrowsWhenChannelIsNotSupported(): void
    {
        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn(null);

        $event = new GuardEvent($order, new Marking(), $this->addressTransition());

        $this->expectException(\InvalidArgumentException::class);

        ($this->guard)($event);
    }

    private function createEvent(ChannelOnePageCheckoutAwareInterface $channel, Transition $transition): GuardEvent
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        return new GuardEvent($order, new Marking(), $transition);
    }

    private function addressTransition(): Transition
    {
        return new Transition(
            OrderCheckoutTransitions::TRANSITION_ADDRESS,
            OrderCheckoutStates::STATE_CART,
            OrderCheckoutStates::STATE_ADDRESSED,
        );
    }

    private function selectShippingTransition(): Transition
    {
        return new Transition(
            OrderCheckoutTransitions::TRANSITION_SELECT_SHIPPING,
            OrderCheckoutStates::STATE_ADDRESSED,
            OrderCheckoutStates::STATE_SHIPPING_SELECTED,
        );
    }

    private function selectPaymentTransition(): Transition
    {
        return new Transition(
            OrderCheckoutTransitions::TRANSITION_SELECT_PAYMENT,
            OrderCheckoutStates::STATE_SHIPPING_SELECTED,
            OrderCheckoutStates::STATE_PAYMENT_SELECTED,
        );
    }
}
