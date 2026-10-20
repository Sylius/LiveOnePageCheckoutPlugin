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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Factory;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\ItemPreview;
use Webmozart\Assert\Assert;

final readonly class ItemsPreviewFactory implements ItemsPreviewFactoryInterface
{
    public function create(OrderInterface $order): array
    {
        $previews = [];

        foreach ($order->getItems() as $item) {
            $id = $item->getId();
            Assert::integer($id);

            $previews[] = new ItemPreview(
                $id,
                $item->getUnitPrice(),
                $item->getOriginalUnitPrice(),
                $item->getTotal(),
                $item->getDiscountedUnitPrice(),
                $item->getSubtotal(),
            );
        }

        return $previews;
    }
}
