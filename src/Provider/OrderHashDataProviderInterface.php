<?php

namespace Sylius\LiveOnePageCheckoutPlugin\Provider;

use Sylius\Component\Core\Model\OrderInterface;

interface OrderHashDataProviderInterface
{
    public function getData(OrderInterface $order): array;
}