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

class OrderSummary
{
    public function __construct(
        public readonly int $itemsTotal,
        public readonly int $itemsSubtotal,
        public readonly int $total,
        public readonly int $taxTotal,
        public readonly int $shippingTaxTotal,
        public readonly int $taxExcludedTotal,
        public readonly int $taxIncludedTotal,
        public readonly int $shippingTotal,
        public readonly int $orderPromotionTotal,
        public readonly int $orderAndItemPromotionTotal,
        public readonly int $shippingPromotionTotal,
        public readonly ?string $promotionCoupon,
    ) {
    }
}
