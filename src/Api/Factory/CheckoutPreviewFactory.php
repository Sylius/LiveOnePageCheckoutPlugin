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
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\CheckoutPreview;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasherInterface;

final readonly class CheckoutPreviewFactory implements CheckoutPreviewFactoryInterface
{
    public function __construct(
        private OrderSummaryFactoryInterface $orderSummaryFactory,
        private ShipmentPreviewFactoryInterface $shipmentPreviewFactory,
        private PaymentPreviewFactoryInterface $paymentPreviewFactory,
        private OrderHasherInterface $orderHasher,
        private AddressPreviewFactoryInterface $addressPreviewFactory,
    ) {
    }

    public function create(OrderInterface $order): CheckoutPreview
    {
        return new CheckoutPreview(
            $this->orderSummaryFactory->create($order),
            $this->addressPreviewFactory->create($order->getBillingAddress()),
            $this->addressPreviewFactory->create($order->getShippingAddress()),
            $this->shipmentPreviewFactory->create($order),
            $this->paymentPreviewFactory->create($order),
            $this->orderHasher->hash($order),
        );
    }
}
