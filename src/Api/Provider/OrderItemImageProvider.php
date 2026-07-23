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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Provider;

use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;

final class OrderItemImageProvider implements OrderItemImageProviderInterface
{
    private const THUMBNAIL_TYPE = 'thumbnail';

    public function provide(OrderItemInterface $item): ?ImageInterface
    {
        $variant = $item->getVariant();

        if ($variant === null) {
            return null;
        }

        if ($variant->hasImages()) {
            $image = $variant->getImagesByType(self::THUMBNAIL_TYPE)->first() ?: $variant->getImages()->first();

            return $image instanceof ImageInterface ? $image : null;
        }

        $product = $variant->getProduct();

        if (!$product instanceof ProductInterface) {
            return null;
        }

        $image = $product->getImagesByType(self::THUMBNAIL_TYPE)->first() ?: $product->getImages()->first();

        return $image instanceof ImageInterface ? $image : null;
    }
}
