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

namespace Sylius\LiveOnePageCheckoutPlugin\Ensurer;

use Sylius\Component\Core\Factory\AddressFactoryInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Factory\PaymentFactoryInterface;
use Sylius\Resource\Factory\FactoryInterface;

final readonly class OrderObjectsEnsurer implements OrderObjectsEnsurerInterface
{
    /**
     * @param AddressFactoryInterface<AddressInterface> $addressFactory
     * @param FactoryInterface<ShipmentInterface> $shipmentFactory
     * @param PaymentFactoryInterface<PaymentInterface> $paymentFactory
     */
    public function __construct(
        private AddressFactoryInterface $addressFactory,
        private FactoryInterface $shipmentFactory,
        private PaymentFactoryInterface $paymentFactory,
    ) {
    }

    public function ensure(OrderInterface $order): void
    {
        if (null === $order->getShippingAddress()) {
            $order->setShippingAddress($this->addressFactory->createNew());
        }

        if (null === $order->getBillingAddress()) {
            $order->setBillingAddress($this->addressFactory->createNew());
        }

        if ($order->isShippingRequired() && $order->getShipments()->isEmpty()) {
            $order->addShipment($this->shipmentFactory->createNew());
        }

        $currencyCode = $order->getCurrencyCode();

        if (null !== $currencyCode && $order->getPayments()->isEmpty()) {
            $order->addPayment(
                $this->paymentFactory->createWithAmountAndCurrencyCode($order->getTotal(), $currencyCode),
            );
        }
    }
}
