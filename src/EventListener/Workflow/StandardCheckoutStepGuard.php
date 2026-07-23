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

namespace Sylius\LiveOnePageCheckoutPlugin\EventListener\Workflow;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Webmozart\Assert\Assert;

final readonly class StandardCheckoutStepGuard
{
    public function __construct(
        private CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
    ) {
    }

    public function __invoke(GuardEvent $event): void
    {
        $order = $event->getSubject();
        Assert::isInstanceOf($order, OrderInterface::class);

        $channel = $order->getChannel();
        Assert::isInstanceOf($channel, ChannelOnePageCheckoutAwareInterface::class);

        if ($this->checkoutEnabledChecker->isEnabled($channel)) {
            $event->setBlocked(true);
        }
    }
}
