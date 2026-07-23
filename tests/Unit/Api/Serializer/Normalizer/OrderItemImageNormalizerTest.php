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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Serializer\Normalizer;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProviderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Serializer\Normalizer\OrderItemImageNormalizer;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class OrderItemImageNormalizerTest extends TestCase
{
    private CacheManager&MockObject $cacheManager;

    private RequestStack $requestStack;

    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private OrderItemImageProviderInterface&MockObject $orderItemImageProvider;

    private NormalizerInterface&MockObject $innerNormalizer;

    private OrderItemImageNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->requestStack = new RequestStack();
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);
        $this->orderItemImageProvider = $this->createMock(OrderItemImageProviderInterface::class);

        $this->normalizer = new OrderItemImageNormalizer(
            $this->cacheManager,
            $this->requestStack,
            $this->checkoutEnabledChecker,
            $this->orderItemImageProvider,
            'sylius_shop_product_small_thumbnail',
        );

        $this->innerNormalizer = $this->createMock(NormalizerInterface::class);
        $this->normalizer->setNormalizer($this->innerNormalizer);
        $this->innerNormalizer->method('normalize')->willReturn(['id' => 1]);
    }

    public function testSupportsOnlyOrderItems(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization($this->createMock(OrderItemInterface::class)));
        self::assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testDoesNotSupportNormalizationWhenAlreadyCalled(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(
            $this->createMock(OrderItemInterface::class),
            null,
            ['sylius_live_one_page_checkout_order_item_image_normalizer_already_called' => true],
        ));
    }

    public function testAddsResolvedImageWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $item = $this->createItemWithChannel(true);
        $image = $this->createMock(ImageInterface::class);
        $image->method('getPath')->willReturn('image.jpg');

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn($image);

        $this->cacheManager->expects(self::once())
            ->method('getBrowserPath')
            ->with('image.jpg', 'sylius_shop_product_small_thumbnail')
            ->willReturn('https://example.com/cache/image.jpg');

        $result = $this->normalizer->normalize($item);

        self::assertSame('https://example.com/cache/image.jpg', $result['image']);
    }

    public function testAddsNullImageWhenProviderFindsNoImage(): void
    {
        $item = $this->createItemWithChannel(true);

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn(null);

        $this->cacheManager->expects(self::never())->method('getBrowserPath');

        $result = $this->normalizer->normalize($item);

        self::assertArrayHasKey('image', $result);
        self::assertNull($result['image']);
    }

    public function testAddsNullImageWhenImagePathIsNull(): void
    {
        $item = $this->createItemWithChannel(true);
        $image = $this->createMock(ImageInterface::class);
        $image->method('getPath')->willReturn(null);

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn($image);

        $this->cacheManager->expects(self::never())->method('getBrowserPath');

        $result = $this->normalizer->normalize($item);

        self::assertArrayHasKey('image', $result);
        self::assertNull($result['image']);
    }

    public function testDoesNotAddImageWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $item = $this->createItemWithChannel(false);

        $this->orderItemImageProvider->expects(self::never())->method('provide');
        $this->cacheManager->expects(self::never())->method('getBrowserPath');

        $result = $this->normalizer->normalize($item);

        self::assertArrayNotHasKey('image', $result);
    }

    public function testDoesNotAddImageWhenOrderIsNull(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getOrder')->willReturn(null);

        $this->orderItemImageProvider->expects(self::never())->method('provide');

        $result = $this->normalizer->normalize($item);

        self::assertArrayNotHasKey('image', $result);
    }

    public function testDoesNotAddImageWhenChannelIsNotOnePageCheckoutAware(): void
    {
        $channel = $this->createMock(ChannelInterface::class);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getOrder')->willReturn($order);

        $this->orderItemImageProvider->expects(self::never())->method('provide');

        $result = $this->normalizer->normalize($item);

        self::assertArrayNotHasKey('image', $result);
    }

    public function testUsesCustomImageFilterWhenProvidedInQuery(): void
    {
        $item = $this->createItemWithChannel(true);
        $image = $this->createMock(ImageInterface::class);
        $image->method('getPath')->willReturn('image.jpg');

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn($image);

        $this->requestStack->push(Request::create('/test', 'GET', ['imageFilter' => 'custom_filter']));

        $this->cacheManager->expects(self::once())
            ->method('getBrowserPath')
            ->with('image.jpg', 'custom_filter')
            ->willReturn('https://example.com/cache/custom.jpg');

        $result = $this->normalizer->normalize($item);

        self::assertSame('https://example.com/cache/custom.jpg', $result['image']);
    }

    public function testThrowsInvalidArgumentExceptionWhenFilterIsNotConfigured(): void
    {
        $item = $this->createItemWithChannel(true);
        $image = $this->createMock(ImageInterface::class);
        $image->method('getPath')->willReturn('image.jpg');

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn($image);

        $this->cacheManager->method('getBrowserPath')->willThrowException(new NonExistingFilterException());

        self::expectException(InvalidArgumentException::class);

        $this->normalizer->normalize($item);
    }

    /**
     * @return OrderItemInterface&MockObject
     */
    private function createItemWithChannel(bool $onePageCheckoutEnabled): OrderItemInterface
    {
        $channel = $this->createMockForIntersectionOfInterfaces([
            ChannelInterface::class,
            ChannelOnePageCheckoutAwareInterface::class,
        ]);

        $this->checkoutEnabledChecker->method('isEnabled')
            ->with($channel)
            ->willReturn($onePageCheckoutEnabled);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getOrder')->willReturn($order);

        return $item;
    }
}
