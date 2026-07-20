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

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\NotesApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final class NotesApplierTest extends TestCase
{
    private NotesApplier $applier;

    protected function setUp(): void
    {
        $this->applier = new NotesApplier();
    }

    public function testImplementsCheckoutApplierInterface(): void
    {
        self::assertInstanceOf(CheckoutApplierInterface::class, $this->applier);
    }

    public function testApplesNotesWhenProvided(): void
    {
        $command = new Checkout(
            orderTokenValue: 'token123',
            notes: 'Please send quickly',
        );

        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::once())
            ->method('setNotes')
            ->with('Please send quickly');

        $this->applier->apply($order, $command);
    }

    public function testDoesNotApplyNotesWhenNotProvided(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');

        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::never())->method('setNotes');

        $this->applier->apply($order, $command);
    }

    public function testDoesNotApplyNullNotes(): void
    {
        $command = new Checkout(
            orderTokenValue: 'token123',
            notes: null,
        );

        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::never())->method('setNotes');

        $this->applier->apply($order, $command);
    }
}
