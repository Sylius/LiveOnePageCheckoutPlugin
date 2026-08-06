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

use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Shipping\Calculator\CalculatorInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\ShippingMethodPreview;

final readonly class ShippingMethodPreviewFactory implements ShippingMethodPreviewFactoryInterface
{
    public function __construct(
        private ServiceRegistryInterface $shippingCalculators,
    ) {
    }

    public function create(ShipmentInterface $shipment, ShippingMethodInterface $shippingMethod): ShippingMethodPreview
    {
        /** @var CalculatorInterface $calculator */
        $calculator = $this->shippingCalculators->get((string) $shippingMethod->getCalculator());

        return new ShippingMethodPreview(
            $shippingMethod->getCode(),
            $shippingMethod->getName(),
            $shippingMethod->getDescription(),
            $calculator->calculate($shipment, $shippingMethod->getConfiguration()),
        );
    }
}
