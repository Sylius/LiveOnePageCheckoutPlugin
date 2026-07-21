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

use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\ShopBundle\EventListener\OrderIntegrityCheckerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OrderConsistencyCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Sylius\LiveOnePageCheckoutPlugin\Provider\SubmittedHashProviderInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final readonly class OrderIntegrityChecker implements OrderIntegrityCheckerInterface
{
    public function __construct(
        private OrderIntegrityCheckerInterface $decoratedChecker,
        private CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
        private OrderConsistencyCheckerInterface $consistencyChecker,
        private SubmittedHashProviderInterface $submittedHashProvider,
        private OrderProcessorInterface $orderProcessor,
        private RouterInterface $router,
        private ObjectManager $manager,
    ) {
    }

    public function check(GenericEvent $event): void
    {
        $order = $event->getSubject();
        Assert::isInstanceOf($order, OrderInterface::class);

        $channel = $order->getChannel();
        Assert::isInstanceOf($channel, ChannelOnePageCheckoutAwareInterface::class);

        if (false === $this->checkoutEnabledChecker->isEnabled($channel)) {
            $this->decoratedChecker->check($event);

            return;
        }

        $submittedHash = $this->submittedHashProvider->getSubmittedHash();
        $this->orderProcessor->process($order);

        if ($this->consistencyChecker->isConsistent($order, $submittedHash)) {
            return;
        }

        $event->stop('sylius_live_one_page_checkout.checkout.state_mismatch');
        $event->setResponse(new RedirectResponse($this->router->generate('sylius_live_one_page_checkout_shop_checkout')));

        $this->manager->persist($order);
        $this->manager->flush();
    }
}
