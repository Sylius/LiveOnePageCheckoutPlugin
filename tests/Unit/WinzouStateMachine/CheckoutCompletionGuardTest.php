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
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Sylius\LiveOnePageCheckoutPlugin\WinzouStateMachine\CheckoutCompletionGuard;
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

    public function testBlocksStandardCompletionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        self::assertFalse(($this->guard)($this->createStandardOrder($channel)));
    }

    public function testAllowsStandardCompletionWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(false);

        self::assertTrue(($this->guard)($this->createStandardOrder($channel)));
    }

    public function testAllowsCartCompletionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        self::assertTrue(($this->guard)($this->createCartOrder($channel)));
    }

    public function testBlocksCartCompletionWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(false);

        self::assertFalse(($this->guard)($this->createCartOrder($channel)));
    }

    public function testThrowsWhenChannelIsNotSupported(): void
    {
        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $this->expectException(\InvalidArgumentException::class);

        ($this->guard)($this->createCartOrder(null));
    }

    private function createCartOrder(?ChannelOnePageCheckoutAwareInterface $channel): OrderInterface&MockObject
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCheckoutState')->willReturn(OrderCheckoutStates::STATE_CART);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }

    private function createStandardOrder(?ChannelOnePageCheckoutAwareInterface $channel): OrderInterface&MockObject
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCheckoutState')->willReturn(OrderCheckoutStates::STATE_PAYMENT_SELECTED);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }
}
