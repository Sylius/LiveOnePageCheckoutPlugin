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

namespace Sylius\LiveOnePageCheckoutPlugin\Entity;

use Sylius\Component\Core\Model\AdjustmentInterface;

trait OrderShippingTaxTotalTrait
{
    public function getShippingTaxTotal(): int
    {
        $shippingTaxTotal = 0;

        foreach ($this->getShipments() as $shipment) {
            foreach ($shipment->getAdjustments(AdjustmentInterface::TAX_ADJUSTMENT) as $taxAdjustment) {
                $shippingTaxTotal += $taxAdjustment->getAmount();
            }
        }

        return $shippingTaxTotal;
    }
}
