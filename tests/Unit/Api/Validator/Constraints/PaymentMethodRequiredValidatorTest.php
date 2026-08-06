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
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
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
    /** @var PaymentMethodRepositoryInterface<PaymentMethodInterface>&MockObject */
    private PaymentMethodRepositoryInterface&MockObject $paymentMethodRepository;

    protected function setUp(): void
    {
        $this->paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new PaymentMethodRequiredValidator($this->paymentMethodRepository);
    }

    public function testViolationWhenNoPaymentsProvided(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', payments: []);

        $this->paymentMethodRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new PaymentMethodRequired());

        $this->buildViolation('sylius.payment.method.not_blank')
            ->atPath('property.path.payments')
            ->assertRaised()
        ;
    }

    public function testViolationWhenPaymentMethodCodeIsMissing(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', payments: [[]]);

        $this->paymentMethodRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new PaymentMethodRequired());

        $this->buildViolation('sylius.payment.method.not_blank')
            ->atPath('property.path.payments')
            ->assertRaised()
        ;
    }

    public function testViolationWhenPaymentMethodCodeIsBlank(): void
    {
        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => '  ']]);

        $this->paymentMethodRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new PaymentMethodRequired());

        $this->buildViolation('sylius.payment.method.not_blank')
            ->atPath('property.path.payments')
            ->assertRaised()
        ;
    }

    public function testViolationWhenPaymentMethodCodeDoesNotExist(): void
    {
        $this->paymentMethodRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'UNKNOWN'])
            ->willReturn(null)
        ;

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => 'UNKNOWN']]);
        $this->validator->validate($command, new PaymentMethodRequired());

        $this->buildViolation('sylius.payment_method.not_exist')
            ->atPath('property.path.payments')
            ->setParameter('%code%', 'UNKNOWN')
            ->assertRaised()
        ;
    }

    public function testNoViolationWhenPaymentMethodCodeIsProvidedForEveryPayment(): void
    {
        $this->paymentMethodRepository->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturn($this->createMock(PaymentMethodInterface::class))
        ;

        $command = new Checkout(orderTokenValue: 'token123', payments: [
            ['methodCode' => 'CASH_ON_DELIVERY'],
            ['methodCode' => 'BANK_TRANSFER'],
        ]);
        $this->validator->validate($command, new PaymentMethodRequired());

        $this->assertNoViolation();
    }
}
