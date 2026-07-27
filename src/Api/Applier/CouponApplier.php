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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Applier;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Promotion\Model\PromotionCouponInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final readonly class CouponApplier implements CheckoutApplierInterface
{
    /** @param PromotionCouponRepositoryInterface<PromotionCouponInterface> $promotionCouponRepository */
    public function __construct(
        private PromotionCouponRepositoryInterface $promotionCouponRepository,
    ) {
    }

    public function apply(OrderInterface $order, Checkout $command): void
    {
        $couponCode = $command->couponCode;

        if (null === $couponCode || '' === $couponCode) {
            $order->setPromotionCoupon(null);

            return;
        }

        $order->setPromotionCoupon($this->promotionCouponRepository->findOneBy(['code' => $couponCode]));
    }
}
