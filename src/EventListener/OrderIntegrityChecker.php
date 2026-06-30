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

namespace Sylius\LiveOnePageCheckoutPlugin\EventListener;

use Sylius\Bundle\ShopBundle\EventListener\OrderIntegrityCheckerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Model\ChannelInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

final readonly class OrderIntegrityChecker implements OrderIntegrityCheckerInterface
{
    public function __construct(
        private OrderIntegrityCheckerInterface $decoratedChecker,
        private CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
    ) {
    }

    public function check(GenericEvent $event): void
    {
        $order = $event->getSubject();
        Assert::isInstanceOf($order, OrderInterface::class);

        $channel = $order->getChannel();
        Assert::isInstanceOf($channel, ChannelInterface::class);

        if ($this->checkoutEnabledChecker->isEnabled($channel)) {
            return;
        }

        $this->decoratedChecker->check($event);
    }
}
