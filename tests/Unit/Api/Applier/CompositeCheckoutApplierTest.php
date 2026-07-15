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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Applier;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CompositeCheckoutApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final class CompositeCheckoutApplierTest extends TestCase
{
    public function testImplementsCheckoutApplierInterface(): void
    {
        $applier = new CompositeCheckoutApplier([]);
        self::assertInstanceOf(CheckoutApplierInterface::class, $applier);
    }

    public function testCallsAllAppliers(): void
    {
        /** @var CheckoutApplierInterface&MockObject $applier1 */
        $applier1 = $this->createMock(CheckoutApplierInterface::class);
        /** @var CheckoutApplierInterface&MockObject $applier2 */
        $applier2 = $this->createMock(CheckoutApplierInterface::class);
        /** @var CheckoutApplierInterface&MockObject $applier3 */
        $applier3 = $this->createMock(CheckoutApplierInterface::class);

        $order = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token123');

        $applier1->expects(self::once())->method('apply')->with($order, $command);
        $applier2->expects(self::once())->method('apply')->with($order, $command);
        $applier3->expects(self::once())->method('apply')->with($order, $command);

        $composite = new CompositeCheckoutApplier([$applier1, $applier2, $applier3]);
        $composite->apply($order, $command);
    }

    public function testHandlesEmptyAppliers(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::never())->method('setShippingAddress');

        $command = new Checkout(orderTokenValue: 'token123');

        $composite = new CompositeCheckoutApplier([]);
        $composite->apply($order, $command);
    }

    public function testPreservesOrderOfAppliers(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token123');

        $calls = [];

        /** @var CheckoutApplierInterface&MockObject $applier1 */
        $applier1 = $this->createMock(CheckoutApplierInterface::class);
        $applier1->expects(self::once())
            ->method('apply')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'applier1';
            });

        /** @var CheckoutApplierInterface&MockObject $applier2 */
        $applier2 = $this->createMock(CheckoutApplierInterface::class);
        $applier2->expects(self::once())
            ->method('apply')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'applier2';
            });

        /** @var CheckoutApplierInterface&MockObject $applier3 */
        $applier3 = $this->createMock(CheckoutApplierInterface::class);
        $applier3->expects(self::once())
            ->method('apply')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'applier3';
            });

        $composite = new CompositeCheckoutApplier([$applier1, $applier2, $applier3]);
        $composite->apply($order, $command);

        self::assertSame(['applier1', 'applier2', 'applier3'], $calls);
    }

    public function testIterableCanBeTraversedMultipleTimes(): void
    {
        /** @var CheckoutApplierInterface&MockObject $applier1 */
        $applier1 = $this->createMock(CheckoutApplierInterface::class);
        /** @var CheckoutApplierInterface&MockObject $applier2 */
        $applier2 = $this->createMock(CheckoutApplierInterface::class);

        $applier1->expects(self::exactly(2))->method('apply');
        $applier2->expects(self::exactly(2))->method('apply');

        $order = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token123');

        $composite = new CompositeCheckoutApplier([$applier1, $applier2]);

        $composite->apply($order, $command);
        $composite->apply($order, $command);
    }
}
