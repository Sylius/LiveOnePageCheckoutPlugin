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

use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\ResettableCartContextInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelInterface;
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
        Assert::isInstanceOf($channel, ChannelInterface::class);

        if (false === $this->checkoutEnabledChecker->isEnabled($channel)) {
            return $cart;
        }

        if (null !== $cart->getShippingAddress()) {
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

        $clonedAddress = clone $defaultAddress;
        $clonedAddress->setCustomer(null);
        $cart->setShippingAddress($clonedAddress);

        return $cart;
    }

    public function reset(): void
    {
        $this->decoratedContext->reset();
    }
}
