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
use Sylius\Bundle\ApiBundle\Serializer\Normalizer\ImageNormalizer;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProviderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class OrderItemImageNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'sylius_live_one_page_checkout_order_item_image_normalizer_already_called';

    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly RequestStack $requestStack,
        private readonly CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
        private readonly OrderItemImageProviderInterface $orderItemImageProvider,
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
        $context[self::ALREADY_CALLED] = true;

        /** @var array<string, mixed> $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        if ($object instanceof OrderItemInterface && $this->supportsOnePageCheckout($object)) {
            $data['image'] = $this->resolveImage($object);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof OrderItemInterface;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [OrderItemInterface::class => false];
    }

    private function supportsOnePageCheckout(OrderItemInterface $item): bool
    {
        $order = $item->getOrder();

        if (!$order instanceof OrderInterface) {
            return false;
        }

        $channel = $order->getChannel();

        if (!$channel instanceof ChannelOnePageCheckoutAwareInterface) {
            return false;
        }

        return $this->checkoutEnabledChecker->isEnabled($channel);
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
