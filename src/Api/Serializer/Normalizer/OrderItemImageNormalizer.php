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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Serializer\Normalizer;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\Serializer\Normalizer\ImageNormalizer;
use Sylius\Bundle\ApiBundle\Serializer\SerializationGroupsSupportTrait;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

final class OrderItemImageNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use SerializationGroupsSupportTrait;

    private const ALREADY_CALLED = 'sylius_live_one_page_checkout_order_item_image_normalizer_already_called';

    /** @param array<string> $serializationGroups */
    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly RequestStack $requestStack,
        private readonly SectionProviderInterface $sectionProvider,
        private readonly OrderItemImageProviderInterface $orderItemImageProvider,
        private readonly array $serializationGroups,
        private readonly string $defaultFilter,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        Assert::isInstanceOf($object, OrderItemInterface::class);
        Assert::keyNotExists($context, self::ALREADY_CALLED);
        Assert::isInstanceOf($this->sectionProvider->getSection(), ShopApiSection::class);
        Assert::true($this->supportsSerializationGroups($context, $this->serializationGroups));

        $context[self::ALREADY_CALLED] = true;

        /** @var array<string, mixed> $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        $data['image'] = $this->resolveImage($object);

        return $data;
    }

    /** @param array<string, mixed> $context */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return
            $data instanceof OrderItemInterface &&
            $this->sectionProvider->getSection() instanceof ShopApiSection &&
            $this->supportsSerializationGroups($context, $this->serializationGroups)
        ;
    }

    /** @return array<class-string, bool> */
    public function getSupportedTypes(?string $format): array
    {
        return [OrderItemInterface::class => false];
    }

    private function resolveImage(OrderItemInterface $item): ?string
    {
        $image = $this->orderItemImageProvider->provide($item);

        if ($image === null) {
            return null;
        }

        $path = $image->getPath();

        if ($path === null) {
            return null;
        }

        $parsedPath = parse_url($path, \PHP_URL_PATH);

        if (!is_string($parsedPath)) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        $filter = $request?->query->get(ImageNormalizer::FILTER_QUERY_PARAMETER);
        $filter = is_string($filter) && $filter !== '' ? $filter : $this->defaultFilter;

        try {
            return $this->cacheManager->getBrowserPath($parsedPath, $filter);
        } catch (NonExistingFilterException|\OutOfBoundsException) {
            throw new InvalidArgumentException(sprintf('Filter "%s" is not configured.', $filter));
        }
    }
}
