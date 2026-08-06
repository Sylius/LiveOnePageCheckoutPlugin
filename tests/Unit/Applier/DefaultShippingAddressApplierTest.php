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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Applier;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Applier\DefaultShippingAddressApplier;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Channel;

final class DefaultShippingAddressApplierTest extends TestCase
{
    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private DefaultShippingAddressApplier $applier;

    protected function setUp(): void
    {
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);

        $this->applier = new DefaultShippingAddressApplier($this->checkoutEnabledChecker);
    }

    public function testDoesNothingWhenCheckoutDisabled(): void
    {
        $cart = $this->createMock(OrderInterface::class);
        $cart->expects(self::never())->method('getCustomer');
        $cart->expects(self::never())->method('setShippingAddress');

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(false);

        $this->applier->apply($cart, new Channel());
    }

    public function testDoesNothingWhenCartAlreadyHasShippingAddress(): void
    {
        $cart = $this->createMock(OrderInterface::class);
        $cart->method('getShippingAddress')->willReturn($this->createMock(AddressInterface::class));
        $cart->expects(self::never())->method('getCustomer');
        $cart->expects(self::never())->method('setShippingAddress');

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        $this->applier->apply($cart, new Channel());
    }

    public function testDoesNothingWhenCartHasNoCustomer(): void
    {
        $cart = $this->createMock(OrderInterface::class);
        $cart->method('getCustomer')->willReturn(null);
        $cart->expects(self::never())->method('setShippingAddress');

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        $this->applier->apply($cart, new Channel());
    }

    public function testDoesNothingWhenCustomerHasNoDefaultAddress(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getDefaultAddress')->willReturn(null);

        $cart = $this->createMock(OrderInterface::class);
        $cart->method('getCustomer')->willReturn($customer);
        $cart->expects(self::never())->method('setShippingAddress');

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        $this->applier->apply($cart, new Channel());
    }

    public function testSetsClonedDefaultAddressDetachedFromCustomerAsShippingAddress(): void
    {
        $defaultAddress = new Address();
        $defaultAddress->setCustomer($this->createMock(CustomerInterface::class));

        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getDefaultAddress')->willReturn($defaultAddress);

        $capturedAddress = null;

        $cart = $this->createMock(OrderInterface::class);
        $cart->method('getCustomer')->willReturn($customer);
        $cart->expects(self::once())
            ->method('setShippingAddress')
            ->willReturnCallback(static function (AddressInterface $address) use (&$capturedAddress): void {
                $capturedAddress = $address;
            });

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        $this->applier->apply($cart, new Channel());

        self::assertInstanceOf(AddressInterface::class, $capturedAddress);
        self::assertNotSame($defaultAddress, $capturedAddress);
        self::assertNull($capturedAddress->getCustomer());
        self::assertNotNull($defaultAddress->getCustomer());
    }
}
