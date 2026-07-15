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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Security\Voter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Security\Voter\CheckoutAvailabilityVoter;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class CheckoutAvailabilityVoterTest extends TestCase
{
    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private CheckoutAvailabilityVoter $voter;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);

        $this->voter = new CheckoutAvailabilityVoter(
            $this->orderRepository,
            $this->checkoutEnabledChecker,
        );
    }

    public function testImplementsCacheableVoterInterface(): void
    {
        self::assertInstanceOf(CacheableVoterInterface::class, $this->voter);
    }

    public function testSupportsAvailableAttribute(): void
    {
        self::assertTrue($this->voter->supportsAttribute(CheckoutAvailabilityVoter::AVAILABLE));
    }

    public function testDoesNotSupportOtherAttributes(): void
    {
        self::assertFalse($this->voter->supportsAttribute('SOME_OTHER_ATTRIBUTE'));
    }

    public function testSupportsCheckoutType(): void
    {
        self::assertTrue($this->voter->supportsType(Checkout::class));
    }

    public function testDoesNotSupportOtherTypes(): void
    {
        self::assertFalse($this->voter->supportsType(\stdClass::class));
    }

    public function testVotesGrantWhenCheckoutIsEnabled(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $token = $this->createMock(TokenInterface::class);
        $command = new Checkout(orderTokenValue: 'token123');

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($order);

        $this->checkoutEnabledChecker->expects(self::once())
            ->method('isEnabled')
            ->with($channel)
            ->willReturn(true);

        $result = $this->voter->vote($token, $command, [CheckoutAvailabilityVoter::AVAILABLE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVotesDenyWhenCheckoutIsDisabled(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $token = $this->createMock(TokenInterface::class);
        $command = new Checkout(orderTokenValue: 'token123');

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($order);

        $this->checkoutEnabledChecker->expects(self::once())
            ->method('isEnabled')
            ->with($channel)
            ->willReturn(false);

        $result = $this->voter->vote($token, $command, [CheckoutAvailabilityVoter::AVAILABLE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVotesDenyWhenCartNotFound(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $command = new Checkout(orderTokenValue: 'nonexistent');

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('nonexistent')
            ->willReturn(null);

        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $result = $this->voter->vote($token, $command, [CheckoutAvailabilityVoter::AVAILABLE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $command = new Checkout(orderTokenValue: 'token123');

        $result = $this->voter->vote($token, $command, ['UNSUPPORTED_ATTRIBUTE']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainsForUnsupportedSubjectType(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new \stdClass();

        $result = $this->voter->vote($token, $subject, [CheckoutAvailabilityVoter::AVAILABLE]);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainsWhenBothAttributeAndSubjectAreUnsupported(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new \stdClass();

        $result = $this->voter->vote($token, $subject, ['UNSUPPORTED']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testConstantIsCorrect(): void
    {
        self::assertSame('SYLIUS_ONE_PAGE_CHECKOUT_AVAILABLE', CheckoutAvailabilityVoter::AVAILABLE);
    }
}
