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
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Sylius\LiveOnePageCheckoutPlugin\WinzouStateMachine\StandardCheckoutStepGuard;
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

    public function testBlocksTransitionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        self::assertFalse(($this->guard)($this->createOrder($channel)));
    }

    public function testDoesNotBlockTransitionWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $channel = new Channel();
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(false);

        self::assertTrue(($this->guard)($this->createOrder($channel)));
    }

    public function testThrowsWhenChannelIsNotSupported(): void
    {
        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $this->expectException(\InvalidArgumentException::class);

        ($this->guard)($this->createOrder(null));
    }

    private function createOrder(?ChannelOnePageCheckoutAwareInterface $channel): OrderInterface&MockObject
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        return $order;
    }
}
