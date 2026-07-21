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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\EventListener;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ShopBundle\EventListener\OrderIntegrityCheckerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OrderConsistencyCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Sylius\LiveOnePageCheckoutPlugin\EventListener\OrderIntegrityChecker;
use Sylius\LiveOnePageCheckoutPlugin\Provider\SubmittedHashProviderInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

final class OrderIntegrityCheckerTest extends TestCase
{
    private OrderIntegrityCheckerInterface&MockObject $decoratedChecker;

    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private OrderConsistencyCheckerInterface&MockObject $consistencyChecker;

    private SubmittedHashProviderInterface&MockObject $submittedHashProvider;

    private OrderProcessorInterface&MockObject $orderProcessor;

    private RouterInterface&MockObject $router;

    private ObjectManager&MockObject $manager;

    private OrderIntegrityChecker $checker;

    protected function setUp(): void
    {
        $this->decoratedChecker = $this->createMock(OrderIntegrityCheckerInterface::class);
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);
        $this->consistencyChecker = $this->createMock(OrderConsistencyCheckerInterface::class);
        $this->submittedHashProvider = $this->createMock(SubmittedHashProviderInterface::class);
        $this->orderProcessor = $this->createMock(OrderProcessorInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->manager = $this->createMock(ObjectManager::class);

        $this->checker = new OrderIntegrityChecker(
            $this->decoratedChecker,
            $this->checkoutEnabledChecker,
            $this->consistencyChecker,
            $this->submittedHashProvider,
            $this->orderProcessor,
            $this->router,
            $this->manager,
        );
    }

    public function testDelegatesToDecoratedCheckerWhenOnePageCheckoutDisabled(): void
    {
        $event = $this->createEvent();

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(false);
        $this->decoratedChecker->expects(self::once())->method('check')->with($event);
        $this->orderProcessor->expects(self::never())->method('process');

        $this->checker->check($event);
    }

    public function testDoesNothingWhenOnePageCheckoutEnabledAndOrderIsConsistent(): void
    {
        $event = $this->createEvent();

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);
        $this->submittedHashProvider->method('getSubmittedHash')->willReturn('abc123');
        $this->orderProcessor->expects(self::once())->method('process');
        $this->consistencyChecker->method('isConsistent')->with(self::isInstanceOf(OrderInterface::class), 'abc123')->willReturn(true);

        $this->decoratedChecker->expects(self::never())->method('check');
        $this->manager->expects(self::never())->method('persist');
        $this->manager->expects(self::never())->method('flush');

        $this->checker->check($event);

        self::assertFalse($event->isStopped());
        self::assertFalse($event->hasResponse());
    }

    public function testStopsEventAndRedirectsWhenOnePageCheckoutEnabledAndOrderIsInconsistent(): void
    {
        $event = $this->createEvent();

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);
        $this->submittedHashProvider->method('getSubmittedHash')->willReturn('outdated-hash');
        $this->orderProcessor->expects(self::once())->method('process');
        $this->consistencyChecker->method('isConsistent')->willReturn(false);
        $this->router->method('generate')->with('sylius_live_one_page_checkout_shop_checkout')->willReturn('/checkout/one-page');

        $this->decoratedChecker->expects(self::never())->method('check');
        $this->manager->expects(self::once())->method('persist');
        $this->manager->expects(self::once())->method('flush');

        $this->checker->check($event);

        self::assertTrue($event->isStopped());
        self::assertTrue($event->hasResponse());
        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertSame('/checkout/one-page', $event->getResponse()->getTargetUrl());
    }

    private function createEvent(): GenericEvent
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($this->createMock(ChannelOnePageCheckoutAwareInterface::class));

        return new GenericEvent($order);
    }
}
