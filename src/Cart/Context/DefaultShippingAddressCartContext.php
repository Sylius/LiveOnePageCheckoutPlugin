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

namespace Sylius\LiveOnePageCheckoutPlugin\Cart\Context;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\ResettableCartContextInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Webmozart\Assert\Assert;

final readonly class DefaultShippingAddressCartContext implements ResettableCartContextInterface
{
    public function __construct(
        private ResettableCartContextInterface $decoratedContext,
        private CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
    ) {
    }

    public function getCart(): BaseOrderInterface
    {
        $cart = $this->decoratedContext->getCart();
        Assert::isInstanceOf($cart, OrderInterface::class);

        $channel = $cart->getChannel();
        Assert::isInstanceOf($channel, ChannelOnePageCheckoutAwareInterface::class);

        if (false === $this->checkoutEnabledChecker->isEnabled($channel)) {
            return $cart;
        }

        $shippingAddressRequired = $channel->isShippingAddressInCheckoutRequired();

        if (true === $shippingAddressRequired && null !== $cart->getShippingAddress()) {
            return $cart;
        }

        if (false === $shippingAddressRequired && null !== $cart->getBillingAddress()) {
            return $cart;
        }

        $customer = $cart->getCustomer();

        if (!$customer instanceof CustomerInterface) {
            return $cart;
        }

        $defaultAddress = $customer->getDefaultAddress();

        if (null === $defaultAddress) {
            return $cart;
        }

        if (true === $shippingAddressRequired) {
            $cart->setShippingAddress($this->cloneWithoutCustomer($defaultAddress));
        } else {
            $cart->setBillingAddress($this->cloneWithoutCustomer($defaultAddress));
        }

        return $cart;
    }

    public function reset(): void
    {
        $this->decoratedContext->reset();
    }

    private function cloneWithoutCustomer(AddressInterface $address): AddressInterface
    {
        $clonedAddress = clone $address;
        $clonedAddress->setCustomer(null);

        return $clonedAddress;
    }
}
