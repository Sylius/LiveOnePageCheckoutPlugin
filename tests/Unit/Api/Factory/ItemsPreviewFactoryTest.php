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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\ItemPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ItemsPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ItemsPreviewFactoryInterface;

final class ItemsPreviewFactoryTest extends TestCase
{
    private ItemsPreviewFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ItemsPreviewFactory();
    }

    public function testImplementsItemsPreviewFactoryInterface(): void
    {
        self::assertInstanceOf(ItemsPreviewFactoryInterface::class, $this->factory);
    }

    public function testCreatesItemPreviewsForAllItems(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getId')->willReturn(42);
        $item->method('getUnitPrice')->willReturn(1000);
        $item->method('getOriginalUnitPrice')->willReturn(1200);
        $item->method('getTotal')->willReturn(1800);
        $item->method('getDiscountedUnitPrice')->willReturn(950);
        $item->method('getSubtotal')->willReturn(1900);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([$item]));

        $result = $this->factory->create($order);

        self::assertCount(1, $result);
        self::assertInstanceOf(ItemPreview::class, $result[0]);
        self::assertSame(42, $result[0]->id);
        self::assertSame(1000, $result[0]->unitPrice);
        self::assertSame(1200, $result[0]->originalUnitPrice);
        self::assertSame(1800, $result[0]->total);
        self::assertSame(950, $result[0]->discountedUnitPrice);
        self::assertSame(1900, $result[0]->subtotal);
    }

    public function testCreatesItemPreviewWithNullOriginalUnitPrice(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getId')->willReturn(1);
        $item->method('getOriginalUnitPrice')->willReturn(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([$item]));

        $result = $this->factory->create($order);

        self::assertNull($result[0]->originalUnitPrice);
    }

    public function testHandlesEmptyItems(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([]));

        $result = $this->factory->create($order);

        self::assertCount(0, $result);
    }
}
