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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelInterface;
use Sylius\LiveOnePageCheckoutPlugin\Validator\Constraints\OrderPaymentMethodSelected;
use Sylius\LiveOnePageCheckoutPlugin\Validator\Constraints\OrderPaymentMethodSelectedValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<OrderPaymentMethodSelectedValidator>
 */
final class OrderPaymentMethodSelectedValidatorTest extends ConstraintValidatorTestCase
{
    /** @var CheckoutEnabledCheckerInterface&MockObject */
    private CheckoutEnabledCheckerInterface $checkoutEnabledChecker;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);

        return new OrderPaymentMethodSelectedValidator($this->checkoutEnabledChecker);
    }

    public function testDoesNothingWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(false);

        $this->validator->validate($this->createOrder([]), new OrderPaymentMethodSelected());

        $this->assertNoViolation();
    }

    public function testDoesNotAddViolationWhenOrderHasPaymentInCartState(): void
    {
        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        $order = $this->createOrder([$this->createPayment(PaymentInterface::STATE_CART)]);

        $this->validator->validate($order, new OrderPaymentMethodSelected());

        $this->assertNoViolation();
    }

    public function testAddsViolationWhenOrderHasNoPayments(): void
    {
        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        $constraint = new OrderPaymentMethodSelected();

        $this->validator->validate($this->createOrder([]), $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.payments')
            ->assertRaised()
        ;
    }

    public function testAddsViolationWhenOrderHasOnlyNonCartPayments(): void
    {
        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        $constraint = new OrderPaymentMethodSelected();
        $order = $this->createOrder([$this->createPayment(PaymentInterface::STATE_CANCELLED)]);

        $this->validator->validate($order, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.payments')
            ->assertRaised()
        ;
    }

    public function testThrowsExceptionWhenValueIsNotAnOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->validator->validate(new \stdClass(), new OrderPaymentMethodSelected());
    }

    /**
     * @param array<PaymentInterface> $payments
     */
    private function createOrder(array $payments): OrderInterface&MockObject
    {
        $channel = $this->createMock(ChannelInterface::class);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getChannel')->willReturn($channel);
        $order->method('getPayments')->willReturn(new ArrayCollection($payments));

        return $order;
    }

    private function createPayment(string $state): PaymentInterface&MockObject
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getState')->willReturn($state);

        return $payment;
    }
}
