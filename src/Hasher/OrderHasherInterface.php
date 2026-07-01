<?php

declare(strict_types=1);

namespace Sylius\LiveOnePageCheckoutPlugin\Hasher;

use Sylius\Component\Core\Model\OrderInterface;

interface OrderHasherInterface
{
    public function hash(OrderInterface $order): string;
}
