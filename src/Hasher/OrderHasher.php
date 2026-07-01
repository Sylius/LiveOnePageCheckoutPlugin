<?php

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
        return md5(json_encode($this->orderHashDataProvider->getData($order)));
    }
}
