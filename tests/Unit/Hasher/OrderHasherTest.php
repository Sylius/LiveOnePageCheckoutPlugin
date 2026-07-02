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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Hasher;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasher;
use Sylius\LiveOnePageCheckoutPlugin\Provider\OrderHashDataProviderInterface;

final class OrderHasherTest extends TestCase
{
    private OrderHashDataProviderInterface&MockObject $orderHashDataProvider;

    private OrderHasher $hasher;

    protected function setUp(): void
    {
        $this->orderHashDataProvider = $this->createMock(OrderHashDataProviderInterface::class);

        $this->hasher = new OrderHasher($this->orderHashDataProvider);
    }

    public function testHashesJsonEncodedProviderData(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $data = ['total' => 1000, 'promotions' => ['WINTER'], 'shipments' => ['UPS'], 'payments' => ['CASH']];

        $this->orderHashDataProvider->method('getData')->with($order)->willReturn($data);

        self::assertSame(md5((string) json_encode($data)), $this->hasher->hash($order));
    }

    public function testProducesDifferentHashesForDifferentData(): void
    {
        $order = $this->createMock(OrderInterface::class);

        $this->orderHashDataProvider->method('getData')->willReturnOnConsecutiveCalls(
            ['total' => 1000],
            ['total' => 2000],
        );

        $firstHash = $this->hasher->hash($order);
        $secondHash = $this->hasher->hash($order);

        self::assertNotSame($firstHash, $secondHash);
    }
}
