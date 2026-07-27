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

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\AddressApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final class AddressApplierTest extends TestCase
{
    private AddressApplier $applier;

    protected function setUp(): void
    {
        $this->applier = new AddressApplier();
    }

    public function testImplementsChecoutApplierInterface(): void
    {
        self::assertInstanceOf(CheckoutApplierInterface::class, $this->applier);
    }

    public function testAppliesShippingAddressWhenProvided(): void
    {
        $shippingAddress = $this->createMock(AddressInterface::class);
        $command = new Checkout(
            orderTokenValue: 'token123',
            shippingAddress: $shippingAddress,
        );

        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::once())
            ->method('setShippingAddress')
            ->with($shippingAddress);

        $this->applier->apply($order, $command);
    }

    public function testAppliesBillingAddressWhenProvided(): void
    {
        $billingAddress = $this->createMock(AddressInterface::class);
        $command = new Checkout(
            orderTokenValue: 'token123',
            billingAddress: $billingAddress,
        );

        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::once())
            ->method('setBillingAddress')
            ->with($billingAddress);

        $this->applier->apply($order, $command);
    }

    public function testAppliesBothAddressesWhenProvided(): void
    {
        $shippingAddress = $this->createMock(AddressInterface::class);
        $billingAddress = $this->createMock(AddressInterface::class);
        $command = new Checkout(
            orderTokenValue: 'token123',
            billingAddress: $billingAddress,
            shippingAddress: $shippingAddress,
        );

        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::once())
            ->method('setShippingAddress')
            ->with($shippingAddress);
        $order->expects(self::once())
            ->method('setBillingAddress')
            ->with($billingAddress);

        $this->applier->apply($order, $command);
    }

    public function testDoesNotApplyAddressesWhenNotProvided(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');

        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::never())->method('setShippingAddress');
        $order->expects(self::never())->method('setBillingAddress');

        $this->applier->apply($order, $command);
    }

    public function testDoesNotApplyNullAddresses(): void
    {
        $command = new Checkout(
            orderTokenValue: 'token123',
            billingAddress: null,
            shippingAddress: null,
        );

        $order = $this->createMock(OrderInterface::class);
        $order->expects(self::never())->method('setShippingAddress');
        $order->expects(self::never())->method('setBillingAddress');

        $this->applier->apply($order, $command);
    }
}
