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

namespace Sylius\LiveOnePageCheckoutPlugin\Checkout;

use Sylius\Bundle\CoreBundle\Checkout\CheckoutResolver;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final readonly class ChannelAwareCheckoutResolver implements EventSubscriberInterface
{
    public function __construct(
        private CheckoutResolver $decorated,
        private ChannelContextInterface $channelContext,
        private CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
        private RequestMatcherInterface $multiStepCheckoutMatcher,
        private RouterInterface $router,
        private string $onePageRoute,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isOnePageEnabled() && $this->multiStepCheckoutMatcher->matches($request)) {
            $event->setResponse(new RedirectResponse($this->router->generate($this->onePageRoute)));

            return;
        }

        $this->decorated->onKernelRequest($event);
    }

    private function isOnePageEnabled(): bool
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return false;
        }

        if (!$channel instanceof ChannelOnePageCheckoutAwareInterface) {
            return false;
        }

        return $this->checkoutEnabledChecker->isEnabled($channel);
    }
}
