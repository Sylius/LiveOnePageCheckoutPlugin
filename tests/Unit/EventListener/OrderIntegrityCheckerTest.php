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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ShopBundle\EventListener\OrderIntegrityCheckerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\EventListener\OrderIntegrityChecker;
use Sylius\LiveOnePageCheckoutPlugin\Model\ChannelInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;

final class OrderIntegrityCheckerTest extends TestCase
{
    private OrderIntegrityCheckerInterface&MockObject $decoratedChecker;

    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private OrderIntegrityChecker $checker;

    protected function setUp(): void
    {
        $this->decoratedChecker = $this->createMock(OrderIntegrityCheckerInterface::class);
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);

        $this->checker = new OrderIntegrityChecker(
            $this->decoratedChecker,
            $this->checkoutEnabledChecker,
        );
    }

    public function testSkipsDecoratedCheckerWhenOnePageCheckoutEnabled(): void
    {
        $event = $this->createEvent();

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);
        $this->decoratedChecker->expects(self::never())->method('check');

        $this->checker->check($event);
    }

    public function testDelegatesToDecoratedCheckerWhenOnePageCheckoutDisabled(): void
    {
        $event = $this->createEvent();

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(false);
        $this->decoratedChecker->expects(self::once())->method('check')->with($event);

        $this->checker->check($event);
    }

    private function createEvent(): GenericEvent
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($this->createMock(ChannelInterface::class));

        return new GenericEvent($order);
    }
}
