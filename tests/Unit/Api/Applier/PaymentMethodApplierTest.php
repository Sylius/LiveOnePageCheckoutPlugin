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
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\PaymentMethodApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final class PaymentMethodApplierTest extends TestCase
{
    private PaymentMethodsResolverInterface&MockObject $paymentMethodsResolver;

    private PaymentMethodApplier $applier;

    protected function setUp(): void
    {
        $this->paymentMethodsResolver = $this->createMock(PaymentMethodsResolverInterface::class);
        $this->applier = new PaymentMethodApplier($this->paymentMethodsResolver);
    }

    public function testImplementsCheckoutApplierInterface(): void
    {
        self::assertInstanceOf(CheckoutApplierInterface::class, $this->applier);
    }

    public function testAppliesSupportedPaymentMethodByCode(): void
    {
        $method = $this->createMock(PaymentMethodInterface::class);
        $method->method('getCode')->willReturn('BANK_TRANSFER');

        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::once())->method('setMethod')->with($method);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->with($payment)
            ->willReturn([$method]);

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => 'BANK_TRANSFER']]);

        $this->applier->apply($order, $command);
    }

    public function testSetsNullWhenRequestedMethodIsNotSupported(): void
    {
        $supportedMethod = $this->createMock(PaymentMethodInterface::class);
        $supportedMethod->method('getCode')->willReturn('CASH_ON_DELIVERY');

        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::once())->method('setMethod')->with(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->with($payment)
            ->willReturn([$supportedMethod]);

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => 'BANK_TRANSFER']]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenMethodCodeIsNull(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::never())->method('setMethod');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => null]]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenMethodCodeIsEmpty(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->expects(self::never())->method('setMethod');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => '']]);

        $this->applier->apply($order, $command);
    }

    public function testSkipsWhenNoPaymentAtIndex(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([]));

        $this->paymentMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $command = new Checkout(orderTokenValue: 'token123', payments: [['methodCode' => 'BANK_TRANSFER']]);

        $this->applier->apply($order, $command);
    }

    public function testAppliesMethodPerPaymentByPosition(): void
    {
        $bankTransfer = $this->createMock(PaymentMethodInterface::class);
        $bankTransfer->method('getCode')->willReturn('BANK_TRANSFER');
        $cashOnDelivery = $this->createMock(PaymentMethodInterface::class);
        $cashOnDelivery->method('getCode')->willReturn('CASH_ON_DELIVERY');

        $firstPayment = $this->createMock(PaymentInterface::class);
        $firstPayment->expects(self::once())->method('setMethod')->with($bankTransfer);
        $secondPayment = $this->createMock(PaymentInterface::class);
        $secondPayment->expects(self::once())->method('setMethod')->with($cashOnDelivery);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$firstPayment, $secondPayment]));

        $this->paymentMethodsResolver
            ->method('getSupportedMethods')
            ->willReturnMap([
                [$firstPayment, [$bankTransfer]],
                [$secondPayment, [$cashOnDelivery]],
            ]);

        $command = new Checkout(
            orderTokenValue: 'token123',
            payments: [['methodCode' => 'BANK_TRANSFER'], ['methodCode' => 'CASH_ON_DELIVERY']],
        );

        $this->applier->apply($order, $command);
    }

    public function testDoesNothingWhenNoPaymentsRequested(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([]));

        $this->paymentMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $command = new Checkout(orderTokenValue: 'token123', payments: []);

        $this->applier->apply($order, $command);
    }
}
