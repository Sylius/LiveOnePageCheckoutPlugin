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

namespace Sylius\LiveOnePageCheckoutPlugin\Hasher;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Provider\OrderHashDataProviderInterface;

final readonly class OrderHasher implements OrderHasherInterface
{
    public function __construct(private OrderHashDataProviderInterface $orderHashDataProvider)
    {
    }

    public function hash(OrderInterface $order): string
    {
        return md5((string) json_encode($this->orderHashDataProvider->getData($order)));
    }
}
