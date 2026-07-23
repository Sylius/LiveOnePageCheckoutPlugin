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

interface OrderItemImageProviderInterface
{
    public function provide(OrderItemInterface $item): ?ImageInterface;
}
