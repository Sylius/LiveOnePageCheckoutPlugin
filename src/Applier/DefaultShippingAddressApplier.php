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

namespace Sylius\LiveOnePageCheckoutPlugin\Applier;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Webmozart\Assert\Assert;

final readonly class DefaultShippingAddressApplier implements DefaultShippingAddressApplierInterface
{
    public function __construct(
        private CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
    ) {
    }

    public function apply(OrderInterface $cart, ChannelInterface $channel): void
    {
        Assert::isInstanceOf($channel, ChannelOnePageCheckoutAwareInterface::class);

        if (false === $this->checkoutEnabledChecker->isEnabled($channel)) {
            return;
        }

        if (null !== $cart->getShippingAddress()) {
            return;
        }

        $customer = $cart->getCustomer();

        if (!$customer instanceof CustomerInterface) {
            return;
        }

        $defaultAddress = $customer->getDefaultAddress();

        if (null === $defaultAddress) {
            return;
        }

        $cart->setShippingAddress($this->cloneAddress($defaultAddress));
    }

    private function cloneAddress(AddressInterface $address): AddressInterface
    {
        $clonedAddress = clone $address;
        $clonedAddress->setCustomer(null);

        return $clonedAddress;
    }
}
