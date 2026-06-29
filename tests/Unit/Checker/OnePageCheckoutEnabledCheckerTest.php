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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OnePageCheckoutEnabledChecker;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OnePageCheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Model\ChannelInterface;

#[CoversClass(OnePageCheckoutEnabledChecker::class)]
final class OnePageCheckoutEnabledCheckerTest extends TestCase
{
    private OnePageCheckoutEnabledChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new OnePageCheckoutEnabledChecker();
    }

    public function testImplementsCheckerInterface(): void
    {
        self::assertInstanceOf(OnePageCheckoutEnabledCheckerInterface::class, $this->checker);
    }

    public function testReturnsTrueWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('isOnePageCheckoutEnabled')->willReturn(true);

        self::assertTrue($this->checker->isEnabled($channel));
    }

    public function testReturnsFalseWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('isOnePageCheckoutEnabled')->willReturn(false);

        self::assertFalse($this->checker->isEnabled($channel));
    }
}
