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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Checkout;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\CoreBundle\Checkout\CheckoutResolver;
use Sylius\Bundle\CoreBundle\Checkout\CheckoutStateUrlGeneratorInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checkout\ChannelAwareCheckoutResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Channel;

final class ChannelAwareCheckoutResolverTest extends TestCase
{
    private const ONE_PAGE_ROUTE = 'sylius_live_one_page_checkout_shop_checkout';

    private const ONE_PAGE_URL = '/en_US/checkout/one-page';

    private ChannelContextInterface&MockObject $channelContext;

    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private RequestMatcherInterface&MockObject $multiStepCheckoutMatcher;

    private RouterInterface&MockObject $router;

    private RequestMatcherInterface&MockObject $decoratedMatcher;

    private ChannelAwareCheckoutResolver $resolver;

    protected function setUp(): void
    {
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);
        $this->multiStepCheckoutMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->decoratedMatcher = $this->createMock(RequestMatcherInterface::class);
        $decorated = new CheckoutResolver(
            $this->createMock(CartContextInterface::class),
            $this->createMock(CheckoutStateUrlGeneratorInterface::class),
            $this->decoratedMatcher,
            $this->createMock(StateMachineInterface::class),
        );

        $this->resolver = new ChannelAwareCheckoutResolver(
            $decorated,
            $this->channelContext,
            $this->checkoutEnabledChecker,
            $this->multiStepCheckoutMatcher,
            $this->router,
            self::ONE_PAGE_ROUTE,
        );
    }

    public function testDoesNothingForSubRequest(): void
    {
        $this->channelContext->expects(self::never())->method('getChannel');
        $this->multiStepCheckoutMatcher->expects(self::never())->method('matches');
        $this->decoratedMatcher->expects(self::never())->method('matches');

        $event = $this->createRequestEvent(HttpKernelInterface::SUB_REQUEST);
        $this->resolver->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testRedirectsToOnePageWhenEnabledAndOnMultiStepCheckout(): void
    {
        $this->channelContext->method('getChannel')->willReturn(new Channel());
        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);
        $this->multiStepCheckoutMatcher->method('matches')->willReturn(true);
        $this->router->method('generate')->with(self::ONE_PAGE_ROUTE)->willReturn(self::ONE_PAGE_URL);

        $this->decoratedMatcher->expects(self::never())->method('matches');

        $event = $this->createRequestEvent();
        $this->resolver->onKernelRequest($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertSame(self::ONE_PAGE_URL, $event->getResponse()->getTargetUrl());
    }

    public function testDelegatesWhenEnabledButAlreadyOnOnePageRoute(): void
    {
        $this->channelContext->method('getChannel')->willReturn(new Channel());
        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);
        $this->multiStepCheckoutMatcher->method('matches')->willReturn(false);
        $this->router->expects(self::never())->method('generate');

        $this->decoratedMatcher->expects(self::once())->method('matches')->willReturn(false);

        $event = $this->createRequestEvent();
        $this->resolver->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testDelegatesWhenOnePageDisabled(): void
    {
        $this->channelContext->method('getChannel')->willReturn(new Channel());
        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(false);
        $this->multiStepCheckoutMatcher->expects(self::never())->method('matches');
        $this->router->expects(self::never())->method('generate');
        $this->decoratedMatcher->expects(self::once())->method('matches')->willReturn(false);

        $event = $this->createRequestEvent();
        $this->resolver->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testTreatsMissingChannelAsDisabledAndDelegates(): void
    {
        $this->channelContext->method('getChannel')->willThrowException(new ChannelNotFoundException());
        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');
        $this->multiStepCheckoutMatcher->expects(self::never())->method('matches');
        $this->router->expects(self::never())->method('generate');
        $this->decoratedMatcher->expects(self::once())->method('matches')->willReturn(false);

        $event = $this->createRequestEvent();
        $this->resolver->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    private function createRequestEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/en_US/checkout/address'),
            $requestType,
        );
    }
}
