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
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\ShipmentPreview;

final readonly class ShipmentPreviewFactory implements ShipmentPreviewFactoryInterface
{
    public function __construct(
        private ShippingMethodsResolverInterface $shippingMethodsResolver,
        private ShippingMethodPreviewFactoryInterface $shippingMethodPreviewFactory,
    ) {
    }

    public function create(OrderInterface $order): array
    {
        $previews = [];

        foreach ($order->getShipments() as $shipment) {
            $availableMethods = [];
            foreach ($this->shippingMethodsResolver->getSupportedMethods($shipment) as $shippingMethod) {
                $availableMethods[] = $this->shippingMethodPreviewFactory->create($shipment, $shippingMethod);
            }

            $previews[] = new ShipmentPreview(
                $shipment->getMethod()?->getCode(),
                $availableMethods,
            );
        }

        return $previews;
    }
}
