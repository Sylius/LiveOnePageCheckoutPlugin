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

class CheckoutPreview
{
    /**
     * @param list<ItemPreview> $items
     * @param list<ShipmentPreview> $shipments
     * @param list<PaymentPreview> $payments
     */
    public function __construct(
        public readonly OrderSummary $summary,
        public readonly array $items,
        public readonly array $shipments,
        public readonly array $payments,
        public readonly string $hash,
    ) {
    }
}
