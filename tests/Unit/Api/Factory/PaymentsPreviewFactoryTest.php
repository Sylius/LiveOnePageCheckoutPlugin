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
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\PaymentMethodPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\PaymentPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentMethodPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentsPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentsPreviewFactoryInterface;

final class PaymentsPreviewFactoryTest extends TestCase
{
    private PaymentMethodsResolverInterface&MockObject $paymentMethodsResolver;

    private PaymentMethodPreviewFactoryInterface&MockObject $paymentMethodPreviewFactory;

    private PaymentsPreviewFactory $factory;

    protected function setUp(): void
    {
        $this->paymentMethodsResolver = $this->createMock(PaymentMethodsResolverInterface::class);
        $this->paymentMethodPreviewFactory = $this->createMock(PaymentMethodPreviewFactoryInterface::class);
        $this->factory = new PaymentsPreviewFactory($this->paymentMethodsResolver, $this->paymentMethodPreviewFactory);
    }

    public function testImplementsPaymentsPreviewFactoryInterface(): void
    {
        self::assertInstanceOf(PaymentsPreviewFactoryInterface::class, $this->factory);
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

        $preview1 = new PaymentMethodPreview('BANK_TRANSFER', 'Bank transfer', null);
        $preview2 = new PaymentMethodPreview('CASH_ON_DELIVERY', 'Cash on delivery', null);
        $preview3 = new PaymentMethodPreview('BANK_TRANSFER', 'Bank transfer', null);
        $preview4 = new PaymentMethodPreview('CASH_ON_DELIVERY', 'Cash on delivery', null);

        $this->paymentMethodPreviewFactory->method('create')
            ->willReturnMap([
                [$supportedMethod1, $preview1],
                [$supportedMethod2, $preview2],
                [$supportedMethod3, $preview3],
                [$supportedMethod4, $preview4],
            ]);

        $result = $this->factory->create($order);

        self::assertCount(2, $result);
        self::assertInstanceOf(PaymentPreview::class, $result[0]);
        self::assertInstanceOf(PaymentPreview::class, $result[1]);
        self::assertSame('BANK_TRANSFER', $result[0]->selectedMethodCode);
        self::assertSame('CASH_ON_DELIVERY', $result[1]->selectedMethodCode);
        self::assertSame([$preview1, $preview2], $result[0]->availableMethods);
        self::assertSame([$preview3, $preview4], $result[1]->availableMethods);
    }

    public function testCreatesPaymentPreviewWhenPaymentHasNoMethod(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getMethod')->willReturn(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([$payment]));

        $supportedMethod = $this->createMock(PaymentMethodInterface::class);
        $preview = new PaymentMethodPreview('CASH_ON_DELIVERY', 'Cash on delivery', null);

        $this->paymentMethodsResolver->expects(self::once())
            ->method('getSupportedMethods')
            ->with($payment)
            ->willReturn([$supportedMethod]);

        $this->paymentMethodPreviewFactory->method('create')->with($supportedMethod)->willReturn($preview);

        $result = $this->factory->create($order);

        self::assertCount(1, $result);
        self::assertNull($result[0]->selectedMethodCode);
        self::assertSame([$preview], $result[0]->availableMethods);
    }

    public function testHandlesEmptyPayments(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection([]));

        $this->paymentMethodsResolver->expects(self::never())->method('getSupportedMethods');

        $result = $this->factory->create($order);

        self::assertCount(0, $result);
    }
}
