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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Checker;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OrderConsistencyChecker;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasherInterface;

final class OrderConsistencyCheckerTest extends TestCase
{
    private OrderHasherInterface&MockObject $hasher;

    private OrderConsistencyChecker $checker;

    protected function setUp(): void
    {
        $this->hasher = $this->createMock(OrderHasherInterface::class);

        $this->checker = new OrderConsistencyChecker($this->hasher);
    }

    public function testIsConsistentWhenSubmittedHashMatchesComputedHash(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $this->hasher->method('hash')->with($order)->willReturn('abc123');

        self::assertTrue($this->checker->isConsistent($order, 'abc123'));
    }

    public function testIsNotConsistentWhenSubmittedHashDiffersFromComputedHash(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $this->hasher->method('hash')->with($order)->willReturn('abc123');

        self::assertFalse($this->checker->isConsistent($order, 'xyz789'));
    }
}
