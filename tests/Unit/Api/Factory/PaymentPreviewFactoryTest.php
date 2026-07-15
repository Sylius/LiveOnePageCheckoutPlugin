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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\PaymentPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentPreviewFactoryInterface;

final class PaymentPreviewFactoryTest extends TestCase
{
    private PaymentMethodsResolverInterface&MockObject $paymentMethodsResolver;

    private PaymentPreviewFactory $factory;

    protected function setUp(): void
    {
        $this->paymentMethodsResolver = $this->createMock(PaymentMethodsResolverInterface::class);
        $this->factory = new PaymentPreviewFactory($this->paymentMethodsResolver);
    }

    public function testImplementsPaymentPreviewFactoryInterface(): void
    {
        self::assertInstanceOf(PaymentPreviewFactoryInterface::class, $this->factory);
    }

    public function testCreatesPaymentPreviewsForAllPayments(): void
    {
        $method1 = $this->createMock(PaymentMethodInterface::class);
        $method1->method('getCode')->willReturn('BANK_TRANSFER');
        $payment1 = $this->createMock(PaymentInterface::class);
        $payment1->method('getMethod')->willReturn($method1);

        $method2 = $this->createMock(PaymentMethodInterface::class);
        $method2->method('getCode')->willReturn('CASH_ON_DELIVERY');
        $payment2 = $this->createMock(PaymentInterface::class);
        $payment2->method('getMethod')->willReturn($method2);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment1, $payment2]));

        $supportedMethod1 = $this->createMock(PaymentMethodInterface::class);
        $supportedMethod2 = $this->createMock(PaymentMethodInterface::class);
        $supportedMethod3 = $this->createMock(PaymentMethodInterface::class);
        $supportedMethod4 = $this->createMock(PaymentMethodInterface::class);

        $this->paymentMethodsResolver->method('getSupportedMethods')
            ->willReturnMap([
                [$payment1, [$supportedMethod1, $supportedMethod2]],
                [$payment2, [$supportedMethod3, $supportedMethod4]],
            ]);

        $result = $this->factory->create($order);

        self::assertCount(2, $result);
        self::assertInstanceOf(PaymentPreview::class, $result[0]);
        self::assertInstanceOf(PaymentPreview::class, $result[1]);
        self::assertSame('BANK_TRANSFER', $result[0]->selectedMethodCode);
        self::assertSame('CASH_ON_DELIVERY', $result[1]->selectedMethodCode);
        self::assertSame([$supportedMethod1, $supportedMethod2], $result[0]->availableMethods);
        self::assertSame([$supportedMethod3, $supportedMethod4], $result[1]->availableMethods);
    }

    public function testCreatesPaymentPreviewWhenPaymentHasNoMethod(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getMethod')->willReturn(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $supportedMethod = $this->createMock(PaymentMethodInterface::class);

        $this->paymentMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->with($payment)
            ->willReturn([$supportedMethod]);

        $result = $this->factory->create($order);

        self::assertCount(1, $result);
        self::assertNull($result[0]->selectedMethodCode);
        self::assertSame([$supportedMethod], $result[0]->availableMethods);
    }

    public function testHandlesEmptyPayments(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([]));

        $this->paymentMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $result = $this->factory->create($order);

        self::assertCount(0, $result);
    }

    public function testArrayValuesResultsAreReturned(): void
    {
        $supportedMethod1 = $this->createMock(PaymentMethodInterface::class);
        $supportedMethod2 = $this->createMock(PaymentMethodInterface::class);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getMethod')->willReturn(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $this->paymentMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->willReturn([$supportedMethod1, $supportedMethod2]);

        $result = $this->factory->create($order);

        self::assertSame([$supportedMethod1, $supportedMethod2], $result[0]->availableMethods);
    }
}
