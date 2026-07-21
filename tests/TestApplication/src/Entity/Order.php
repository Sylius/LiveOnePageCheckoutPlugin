<?php

declare(strict_types=1);

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Order as BaseOrder;
use Sylius\LiveOnePageCheckoutPlugin\Entity\OrderAndItemPromotionTotalAwareTrait;
use Sylius\LiveOnePageCheckoutPlugin\Entity\OrderInterface;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_order')]
class Order extends BaseOrder implements OrderInterface
{
    use OrderAndItemPromotionTotalAwareTrait;
}
