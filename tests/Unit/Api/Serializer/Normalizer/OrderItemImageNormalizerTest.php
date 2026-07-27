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
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionInterface;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProviderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Serializer\Normalizer\OrderItemImageNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class OrderItemImageNormalizerTest extends TestCase
{
    private const SUPPORTED_GROUP_CONTEXT = ['groups' => ['sylius_live_one_page_checkout:shop:order:show']];

    private CacheManager&MockObject $cacheManager;

    private RequestStack $requestStack;

    private SectionProviderInterface&MockObject $sectionProvider;

    private OrderItemImageProviderInterface&MockObject $orderItemImageProvider;

    private NormalizerInterface&MockObject $innerNormalizer;

    private OrderItemImageNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->requestStack = new RequestStack();
        $this->sectionProvider = $this->createMock(SectionProviderInterface::class);
        $this->sectionProvider->method('getSection')->willReturn(new ShopApiSection());
        $this->orderItemImageProvider = $this->createMock(OrderItemImageProviderInterface::class);

        $this->normalizer = new OrderItemImageNormalizer(
            $this->cacheManager,
            $this->requestStack,
            $this->sectionProvider,
            $this->orderItemImageProvider,
            ['sylius_live_one_page_checkout:shop:order:show'],
            'sylius_shop_product_small_thumbnail',
        );

        $this->innerNormalizer = $this->createMock(NormalizerInterface::class);
        $this->normalizer->setNormalizer($this->innerNormalizer);
        $this->innerNormalizer->method('normalize')->willReturn(['id' => 1]);
    }

    public function testSupportsOnlyOrderItems(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(
            $this->createMock(OrderItemInterface::class),
            null,
            self::SUPPORTED_GROUP_CONTEXT,
        ));
        self::assertFalse($this->normalizer->supportsNormalization(new \stdClass(), null, self::SUPPORTED_GROUP_CONTEXT));
    }

    public function testDoesNotSupportNormalizationWhenAlreadyCalled(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(
            $this->createMock(OrderItemInterface::class),
            null,
            self::SUPPORTED_GROUP_CONTEXT + ['sylius_live_one_page_checkout_order_item_image_normalizer_already_called' => true],
        ));
    }

    public function testDoesNotSupportNormalizationWhenGroupIsNotSupported(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(
            $this->createMock(OrderItemInterface::class),
            null,
            ['groups' => ['sylius:shop:cart:show']],
        ));
        self::assertFalse($this->normalizer->supportsNormalization($this->createMock(OrderItemInterface::class)));
    }

    public function testDoesNotSupportNormalizationOutsideShopApiSection(): void
    {
        $sectionProvider = $this->createMock(SectionProviderInterface::class);
        $sectionProvider->method('getSection')->willReturn($this->createMock(SectionInterface::class));

        $normalizer = new OrderItemImageNormalizer(
            $this->cacheManager,
            $this->requestStack,
            $sectionProvider,
            $this->orderItemImageProvider,
            ['sylius_live_one_page_checkout:shop:order:show'],
            'sylius_shop_product_small_thumbnail',
        );

        self::assertFalse($normalizer->supportsNormalization(
            $this->createMock(OrderItemInterface::class),
            null,
            self::SUPPORTED_GROUP_CONTEXT,
        ));
    }

    public function testAddsResolvedImage(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $image = $this->createMock(ImageInterface::class);
        $image->method('getPath')->willReturn('image.jpg');

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn($image);

        $this->cacheManager->expects(self::once())
            ->method('getBrowserPath')
            ->with('image.jpg', 'sylius_shop_product_small_thumbnail')
            ->willReturn('https://example.com/cache/image.jpg');

        $result = $this->normalizer->normalize($item, null, self::SUPPORTED_GROUP_CONTEXT);

        self::assertSame('https://example.com/cache/image.jpg', $result['image']);
    }

    public function testAddsNullImageWhenProviderFindsNoImage(): void
    {
        $item = $this->createMock(OrderItemInterface::class);

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn(null);

        $this->cacheManager->expects(self::never())->method('getBrowserPath');

        $result = $this->normalizer->normalize($item, null, self::SUPPORTED_GROUP_CONTEXT);

        self::assertArrayHasKey('image', $result);
        self::assertNull($result['image']);
    }

    public function testAddsNullImageWhenImagePathIsNull(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $image = $this->createMock(ImageInterface::class);
        $image->method('getPath')->willReturn(null);

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn($image);

        $this->cacheManager->expects(self::never())->method('getBrowserPath');

        $result = $this->normalizer->normalize($item, null, self::SUPPORTED_GROUP_CONTEXT);

        self::assertArrayHasKey('image', $result);
        self::assertNull($result['image']);
    }

    public function testUsesCustomImageFilterWhenProvidedInQuery(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $image = $this->createMock(ImageInterface::class);
        $image->method('getPath')->willReturn('image.jpg');

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn($image);

        $this->requestStack->push(Request::create('/test', 'GET', ['imageFilter' => 'custom_filter']));

        $this->cacheManager->expects(self::once())
            ->method('getBrowserPath')
            ->with('image.jpg', 'custom_filter')
            ->willReturn('https://example.com/cache/custom.jpg');

        $result = $this->normalizer->normalize($item, null, self::SUPPORTED_GROUP_CONTEXT);

        self::assertSame('https://example.com/cache/custom.jpg', $result['image']);
    }

    public function testThrowsInvalidArgumentExceptionWhenFilterIsNotConfigured(): void
    {
        $item = $this->createMock(OrderItemInterface::class);
        $image = $this->createMock(ImageInterface::class);
        $image->method('getPath')->willReturn('image.jpg');

        $this->orderItemImageProvider->method('provide')->with($item)->willReturn($image);

        $this->cacheManager->method('getBrowserPath')->willThrowException(new NonExistingFilterException());

        self::expectException(InvalidArgumentException::class);

        $this->normalizer->normalize($item, null, self::SUPPORTED_GROUP_CONTEXT);
    }
}
