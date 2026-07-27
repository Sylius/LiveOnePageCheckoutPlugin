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

class ShipmentPreview
{
    /** @param list<ShippingMethodPreview> $availableMethods */
    public function __construct(
        public readonly ?string $selectedMethodCode,
        public readonly array $availableMethods,
    ) {
    }
}
