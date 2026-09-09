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
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final readonly class ShippingMethodApplier implements CheckoutApplierInterface
{
    /** @param ShippingMethodRepositoryInterface<ShippingMethodInterface> $shippingMethodRepository */
    public function __construct(
        private ShippingMethodRepositoryInterface $shippingMethodRepository,
    ) {
    }

    public function apply(OrderInterface $order, Checkout $command): void
    {
        $shipments = array_values($order->getShipments()->toArray());

        foreach ($command->shipments as $index => $data) {
            $code = $data['methodCode'] ?? null;
            $shipment = $shipments[$index] ?? null;

            if (null === $code || '' === $code || !$shipment instanceof ShipmentInterface) {
                continue;
            }

            $method = $this->shippingMethodRepository->findOneBy(['code' => $code]);

            $shipment->setMethod($method instanceof ShippingMethodInterface ? $method : null);
        }
    }
}
