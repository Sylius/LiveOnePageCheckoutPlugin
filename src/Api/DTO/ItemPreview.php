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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\DTO;

class ItemPreview
{
    public function __construct(
        public readonly int $id,
        public readonly int $unitPrice,
        public readonly ?int $originalUnitPrice,
        public readonly int $total,
        public readonly int $discountedUnitPrice,
        public readonly int $subtotal,
    ) {
    }
}
