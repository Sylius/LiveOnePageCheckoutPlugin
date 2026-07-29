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

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PaymentMethodRequired;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PaymentMethodRequiredValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PaymentMethodRequiredValidator>
 */
final class PaymentMethodRequiredValidatorTest extends ConstraintValidatorTestCase
{
    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new PaymentMethodRequiredValidator($this->orderRepository);
    }

    public function testNoViolationWhenOrderNotFound(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');

        $this->orderRepository->method('findCartByTokenValue')->willReturn(null);

        $this->validator->validate($command, new PaymentMethodRequired());

        $this->assertNoViolation();
    }

    public function testViolationWhenPaymentMethodCodeIsMissing(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$this->createMock(PaymentInterface::class)]));

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123', payments: []);
        $this->validator->validate($command, new PaymentMethodRequired());

        $this->buildViolation('sylius.payment.method.not_blank')
            ->atPath('property.path.payments')
            ->assertRaised()
        ;
    }

    public function testViolationWhenPaymentMethodCodeIsBlank(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$this->createMock(PaymentInterface::class)]));

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => '  ']]);
        $this->validator->validate($command, new PaymentMethodRequired());

        $this->buildViolation('sylius.payment.method.not_blank')
            ->atPath('property.path.payments')
            ->assertRaised()
        ;
    }

    public function testNoViolationWhenPaymentMethodCodeIsProvidedForEveryPayment(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([
            $this->createMock(PaymentInterface::class),
            $this->createMock(PaymentInterface::class),
        ]));

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123', payments: [
            ['methodCode' => 'CASH_ON_DELIVERY'],
            ['methodCode' => 'BANK_TRANSFER'],
        ]);
        $this->validator->validate($command, new PaymentMethodRequired());

        $this->assertNoViolation();
    }
}
