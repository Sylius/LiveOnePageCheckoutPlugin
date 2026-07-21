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

trait OrderAndItemPromotionTotalAwareTrait
{
    public function getOrderAndItemPromotionTotal(): int
    {
        return
            $this->getAdjustmentsTotalRecursively(AdjustmentInterface::ORDER_ITEM_PROMOTION_ADJUSTMENT) +
            $this->getAdjustmentsTotalRecursively(AdjustmentInterface::ORDER_PROMOTION_ADJUSTMENT)
        ;
    }
}
