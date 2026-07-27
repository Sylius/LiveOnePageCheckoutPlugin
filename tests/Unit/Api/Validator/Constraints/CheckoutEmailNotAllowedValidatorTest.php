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
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\CheckoutEmailNotAllowed;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\CheckoutEmailNotAllowedValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CheckoutEmailNotAllowedValidator>
 */
final class CheckoutEmailNotAllowedValidatorTest extends ConstraintValidatorTestCase
{
    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    private UserContextInterface&MockObject $userContext;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new CheckoutEmailNotAllowedValidator($this->orderRepository, $this->userContext);
    }

    public function testNoViolationForGuest(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', email: 'attacker@example.com');

        $this->userContext->expects(self::once())->method('getUser')->willReturn(null);
        $this->orderRepository->expects(self::never())->method('findCartByTokenValue');

        $this->validator->validate($command, new CheckoutEmailNotAllowed());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenCartCannotBeFound(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', email: 'victim@example.com');

        $this->userContext->expects(self::once())->method('getUser')->willReturn($this->createMock(ShopUserInterface::class));
        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn(null)
        ;

        $this->validator->validate($command, new CheckoutEmailNotAllowed());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenOrderHasNoCustomerYet(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', email: 'victim@example.com');
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomer')->willReturn(null);

        $this->userContext->expects(self::once())->method('getUser')->willReturn($this->createMock(ShopUserInterface::class));
        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($order)
        ;

        $this->validator->validate($command, new CheckoutEmailNotAllowed());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenSubmittedEmailMatchesOwnEmail(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', email: 'me@example.com');
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getEmail')->willReturn('me@example.com');
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomer')->willReturn($customer);

        $this->userContext->expects(self::once())->method('getUser')->willReturn($this->createMock(ShopUserInterface::class));
        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($order)
        ;

        $this->validator->validate($command, new CheckoutEmailNotAllowed());

        $this->assertNoViolation();
    }

    public function testViolationWhenSubmittedEmailDoesNotMatchOwnEmail(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', email: 'victim@example.com');
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getEmail')->willReturn('me@example.com');
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomer')->willReturn($customer);

        $this->userContext->expects(self::once())->method('getUser')->willReturn($this->createMock(ShopUserInterface::class));
        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($order)
        ;

        $this->validator->validate($command, new CheckoutEmailNotAllowed());

        $this->buildViolation('sylius_live_one_page_checkout.checkout.email_not_allowed')
            ->atPath('property.path.email')
            ->assertRaised()
        ;
    }
}
