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

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\ResettableCartContextInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Applier\DefaultShippingAddressApplierInterface;
use Webmozart\Assert\Assert;

final readonly class DefaultShippingAddressCartContext implements ResettableCartContextInterface
{
    public function __construct(
        private ResettableCartContextInterface $decoratedContext,
        private DefaultShippingAddressApplierInterface $defaultShippingAddressApplier,
    ) {
    }

    public function getCart(): BaseOrderInterface
    {
        $cart = $this->decoratedContext->getCart();
        Assert::isInstanceOf($cart, OrderInterface::class);

        $channel = $cart->getChannel();
        Assert::isInstanceOf($channel, ChannelInterface::class);

        $this->defaultShippingAddressApplier->apply($cart, $channel);

        return $cart;
    }

    public function reset(): void
    {
        $this->decoratedContext->reset();
    }
}
