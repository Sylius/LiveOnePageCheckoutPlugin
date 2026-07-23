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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProvider;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProviderInterface;

final class OrderItemImageProviderTest extends TestCase
{
    private OrderItemImageProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new OrderItemImageProvider();
    }

    public function testImplementsOrderItemImageProviderInterface(): void
    {
        self::assertInstanceOf(OrderItemImageProviderInterface::class, $this->provider);
    }

    public function testReturnsNullWhenItemHasNoVariant(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getVariant')->willReturn(null);

        self::assertNull($this->provider->provide($item));
    }

    public function testReturnsVariantThumbnailImageWhenVariantHasImages(): void
    {
        $image = $this->createMock(ImageInterface::class);

        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('hasImages')->willReturn(true);
        $variant->method('getImagesByType')->with('thumbnail')->willReturn(new ArrayCollection([$image]));

        $item = $this->createItemWithVariant($variant);

        self::assertSame($image, $this->provider->provide($item));
    }

    public function testFallsBackToVariantFirstImageWhenNoThumbnailAvailable(): void
    {
        $image = $this->createMock(ImageInterface::class);

        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('hasImages')->willReturn(true);
        $variant->method('getImagesByType')->with('thumbnail')->willReturn(new ArrayCollection([]));
        $variant->method('getImages')->willReturn(new ArrayCollection([$image]));

        $item = $this->createItemWithVariant($variant);

        self::assertSame($image, $this->provider->provide($item));
    }

    public function testFallsBackToProductImagesWhenVariantHasNoImages(): void
    {
        $image = $this->createMock(ImageInterface::class);

        $product = $this->createMock(ProductInterface::class);
        $product->method('getImagesByType')->with('thumbnail')->willReturn(new ArrayCollection([]));
        $product->method('getImages')->willReturn(new ArrayCollection([$image]));

        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('hasImages')->willReturn(false);
        $variant->method('getProduct')->willReturn($product);

        $item = $this->createItemWithVariant($variant);

        self::assertSame($image, $this->provider->provide($item));
    }

    public function testReturnsNullWhenVariantHasNoImagesAndProductIsMissing(): void
    {
        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('hasImages')->willReturn(false);
        $variant->method('getProduct')->willReturn(null);

        $item = $this->createItemWithVariant($variant);

        self::assertNull($this->provider->provide($item));
    }

    public function testReturnsNullWhenNeitherVariantNorProductHaveImages(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getImagesByType')->willReturn(new ArrayCollection([]));
        $product->method('getImages')->willReturn(new ArrayCollection([]));

        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('hasImages')->willReturn(false);
        $variant->method('getProduct')->willReturn($product);

        $item = $this->createItemWithVariant($variant);

        self::assertNull($this->provider->provide($item));
    }

    /**
     * @return OrderItemInterface&MockObject
     */
    private function createItemWithVariant(ProductVariantInterface $variant): OrderItemInterface
    {
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getVariant')->willReturn($variant);

        return $item;
    }
}
