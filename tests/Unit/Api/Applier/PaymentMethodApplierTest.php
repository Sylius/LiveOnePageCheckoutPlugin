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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Applier;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\PaymentMethodApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final class PaymentMethodApplierTest extends TestCase
{
    /** @var PaymentMethodRepositoryInterface<PaymentMethodInterface>&MockObject */
    private PaymentMethodRepositoryInterface&MockObject $paymentMethodRepository;

    private PaymentMethodApplier $applier;

    protected function setUp(): void
    {
        $this->paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->applier = new PaymentMethodApplier($this->paymentMethodRepository);
    }

    public function testImplementsCheckoutApplierInterface(): void
    {
        self::assertInstanceOf(CheckoutApplierInterface::class, $this->applier);
    }

    public function testAppliesExistingPaymentMethodByCode(): void
    {
        $method = $this->createMock(PaymentMethodInterface::class);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::once())->method('setMethod')->with($method);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'CASH_ON_DELIVERY'])
            ->willReturn($method);

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => 'CASH_ON_DELIVERY']]);

        $this->applier->apply($order, $command);
    }

    public function testAppliesDisabledMethodAndLeavesItToValidation(): void
    {
        $method = $this->createMock(PaymentMethodInterface::class);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::once())->method('setMethod')->with($method);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'DISABLED_METHOD'])
            ->willReturn($method);

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => 'DISABLED_METHOD']]);

        $this->applier->apply($order, $command);
    }

    public function testSetsNullWhenRequestedMethodDoesNotExist(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::once())->method('setMethod')->with(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'BOGUS'])
            ->willReturn(null);

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => 'BOGUS']]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenMethodCodeIsNull(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::never())->method('setMethod');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodRepository->expects(self::never())->method('findOneBy');

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => null]]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenMethodCodeIsEmpty(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::never())->method('setMethod');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodRepository->expects(self::never())->method('findOneBy');

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => '']]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenNoPaymentAtIndex(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([]));

        $this->paymentMethodRepository->expects(self::never())->method('findOneBy');

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => 'CASH_ON_DELIVERY']]);

        $this->applier->apply($order, $command);
    }

    public function testAppliesMethodPerPaymentByPosition(): void
    {
        $cash = $this->createMock(PaymentMethodInterface::class);
        $stripe = $this->createMock(PaymentMethodInterface::class);

        $firstPayment = $this->createMock(PaymentInterface::class);
        $firstPayment->expects(self::once())->method('setMethod')->with($cash);
        $secondPayment = $this->createMock(PaymentInterface::class);
        $secondPayment->expects(self::once())->method('setMethod')->with($stripe);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$firstPayment, $secondPayment]));

        $this->paymentMethodRepository
            ->method('findOneBy')
            ->willReturnMap([
                [['code' => 'CASH_ON_DELIVERY'], $cash],
                [['code' => 'STRIPE'], $stripe],
            ]);

        $command = new Checkout(
            orderTokenValue: 'token123',
            payments: [['methodCode' => 'CASH_ON_DELIVERY'], ['methodCode' => 'STRIPE']],
        );

        $this->applier->apply($order, $command);
    }

    public function testDoesNothingWhenNoPaymentsRequested(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([]));

        $this->paymentMethodRepository->expects(self::never())->method('findOneBy');

        $command = new Checkout(orderTokenValue: 'token123', payments: []);

        $this->applier->apply($order, $command);
    }
}
