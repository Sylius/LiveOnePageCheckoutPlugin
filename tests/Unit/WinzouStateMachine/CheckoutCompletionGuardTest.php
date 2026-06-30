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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\WinzouStateMachine;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Model\ChannelInterface;
use Sylius\LiveOnePageCheckoutPlugin\WinzouStateMachine\CheckoutCompletionGuard;

final class CheckoutCompletionGuardTest extends TestCase
{
    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private CheckoutCompletionGuard $guard;

    protected function setUp(): void
    {
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);
        $this->guard = new CheckoutCompletionGuard($this->checkoutEnabledChecker);
    }

    public function testAllowsCompletionThatDoesNotOriginateFromCart(): void
    {
        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getCheckoutState')->willReturn(OrderCheckoutStates::STATE_PAYMENT_SELECTED);
        $order->expects(self::never())->method('getChannel');

        self::assertTrue(($this->guard)($order));
    }

    public function testAllowsCartCompletionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        self::assertTrue(($this->guard)($this->createCartOrder($channel)));
    }

    public function testBlocksCartCompletionWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(false);

        self::assertFalse(($this->guard)($this->createCartOrder($channel)));
    }

    public function testThrowsWhenChannelIsNotSupported(): void
    {
        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $this->expectException(\InvalidArgumentException::class);

        ($this->guard)($this->createCartOrder(null));
    }

    private function createCartOrder(?ChannelInterface $channel): OrderInterface&MockObject
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCheckoutState')->willReturn(OrderCheckoutStates::STATE_CART);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }
}
