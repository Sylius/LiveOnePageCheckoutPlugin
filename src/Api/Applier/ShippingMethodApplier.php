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
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final readonly class ShippingMethodApplier implements CheckoutApplierInterface
{
    public function __construct(
        private ShippingMethodsResolverInterface $shippingMethodsResolver,
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

            $shipment->setMethod($this->findSupportedMethod($shipment, $code));
        }
    }

    private function findSupportedMethod(ShipmentInterface $shipment, string $code): ?ShippingMethodInterface
    {
        foreach ($this->shippingMethodsResolver->getSupportedMethods($shipment) as $method) {
            if ($method->getCode() === $code) {
                return $method;
            }
        }

        return null;
    }
}
