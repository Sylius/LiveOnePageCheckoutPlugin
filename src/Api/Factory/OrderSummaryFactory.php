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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Factory;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\OrderSummary;
use Sylius\LiveOnePageCheckoutPlugin\Entity\OrderAndItemPromotionTotalAwareInterface;
use Webmozart\Assert\Assert;

final readonly class OrderSummaryFactory implements OrderSummaryFactoryInterface
{
    public function create(OrderInterface $order): OrderSummary
    {
        Assert::isInstanceOf($order, OrderAndItemPromotionTotalAwareInterface::class);

        return new OrderSummary(
            $order->getItemsTotal(),
            $order->getItemsSubtotal(),
            $order->getTotal(),
            $order->getTaxTotal(),
            $order->getShippingTaxTotal(),
            $order->getTaxExcludedTotal(),
            $order->getTaxIncludedTotal(),
            $order->getShippingTotal(),
            $order->getOrderPromotionTotal(),
            $order->getOrderAndItemPromotionTotal(),
            $order->getShippingPromotionTotal(),
            $order->getPromotionCoupon()?->getCode(),
            $order->getNotes(),
        );
    }
}
