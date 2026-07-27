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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Validator\Constraints;

use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\ApiBundle\Command\Checkout\ChoosePaymentMethod;
use Sylius\Bundle\ApiBundle\Command\Checkout\ChooseShippingMethod;
use Sylius\Bundle\ApiBundle\Command\Checkout\UpdateCart;
use Sylius\Component\Addressing\Model\Address;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\StandardCheckoutStepBlocked;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\StandardCheckoutStepBlockedValidator;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Channel;

/**
 * @extends ConstraintValidatorTestCase<StandardCheckoutStepBlockedValidator>
 */
final class StandardCheckoutStepBlockedValidatorTest extends ConstraintValidatorTestCase
{
    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new StandardCheckoutStepBlockedValidator($this->orderRepository, $this->checkoutEnabledChecker);
    }

    public function testViolationForUpdateCartWithoutAddressWhenOnePageCheckoutEnabled(): void
    {
        $channel = $this->createMock(Channel::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $command = new UpdateCart(orderTokenValue: 'token123');

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        $this->validator->validate($command, new StandardCheckoutStepBlocked());

        $this->buildViolation('sylius_live_one_page_checkout.checkout.standard_checkout_not_available')
            ->assertRaised()
        ;
    }

    public function testNoViolationWhenOrderNotFound(): void
    {
        $command = new UpdateCart(orderTokenValue: 'token123', billingAddress: new Address());

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn(null)
        ;

        $this->validator->validate($command, new StandardCheckoutStepBlocked());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenChannelDoesNotSupportOnePageCheckout(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($this->createMock(ChannelInterface::class));

        $command = new UpdateCart(orderTokenValue: 'token123', billingAddress: new Address());

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $this->checkoutEnabledChecker->expects(self::never())->method('isEnabled');

        $this->validator->validate($command, new StandardCheckoutStepBlocked());

        $this->assertNoViolation();
    }

    public function testNoViolationForUpdateCartWhenOnePageCheckoutDisabled(): void
    {
        $channel = $this->createMock(Channel::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $command = new UpdateCart(orderTokenValue: 'token123', billingAddress: new Address());

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(false);

        $this->validator->validate($command, new StandardCheckoutStepBlocked());

        $this->assertNoViolation();
    }

    public function testViolationForUpdateCartWhenOnePageCheckoutEnabled(): void
    {
        $channel = $this->createMock(Channel::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $command = new UpdateCart(orderTokenValue: 'token123', shippingAddress: new Address());

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        $this->validator->validate($command, new StandardCheckoutStepBlocked());

        $this->buildViolation('sylius_live_one_page_checkout.checkout.standard_checkout_not_available')
            ->assertRaised()
        ;
    }

    public function testViolationForChooseShippingMethodWhenOnePageCheckoutEnabled(): void
    {
        $channel = $this->createMock(Channel::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $command = new ChooseShippingMethod(orderTokenValue: 'token123', shipmentId: 1, shippingMethodCode: 'UPS');

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        $this->validator->validate($command, new StandardCheckoutStepBlocked());

        $this->buildViolation('sylius_live_one_page_checkout.checkout.standard_checkout_not_available')
            ->assertRaised()
        ;
    }

    public function testNoViolationForChooseShippingMethodWhenOnePageCheckoutDisabled(): void
    {
        $channel = $this->createMock(Channel::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $command = new ChooseShippingMethod(orderTokenValue: 'token123', shipmentId: 1, shippingMethodCode: 'UPS');

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(false);

        $this->validator->validate($command, new StandardCheckoutStepBlocked());

        $this->assertNoViolation();
    }

    public function testViolationForChoosePaymentMethodWhenOnePageCheckoutEnabled(): void
    {
        $channel = $this->createMock(Channel::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);

        $command = new ChoosePaymentMethod(orderTokenValue: 'token123', paymentId: 1, paymentMethodCode: 'offline');

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->checkoutEnabledChecker->method('isEnabled')->with($channel)->willReturn(true);

        $this->validator->validate($command, new StandardCheckoutStepBlocked());

        $this->buildViolation('sylius_live_one_page_checkout.checkout.standard_checkout_not_available')
            ->assertRaised()
        ;
    }
}
