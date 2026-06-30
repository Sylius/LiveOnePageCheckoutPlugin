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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Cart\Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\ResettableCartContextInterface;
use Sylius\LiveOnePageCheckoutPlugin\Cart\Context\DefaultShippingAddressCartContext;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Model\ChannelInterface;

#[CoversClass(DefaultShippingAddressCartContext::class)]
final class DefaultShippingAddressCartContextTest extends TestCase
{
    private ResettableCartContextInterface&MockObject $decoratedContext;

    private CheckoutEnabledCheckerInterface&MockObject $checkoutEnabledChecker;

    private DefaultShippingAddressCartContext $context;

    protected function setUp(): void
    {
        $this->decoratedContext = $this->createMock(ResettableCartContextInterface::class);
        $this->checkoutEnabledChecker = $this->createMock(CheckoutEnabledCheckerInterface::class);

        $this->context = new DefaultShippingAddressCartContext(
            $this->decoratedContext,
            $this->checkoutEnabledChecker,
        );
    }

    public function testReturnsCartUntouchedWhenCheckoutDisabled(): void
    {
        $cart = $this->createCart();
        $cart->expects(self::never())->method('getCustomer');
        $cart->expects(self::never())->method('setShippingAddress');

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(false);

        self::assertSame($cart, $this->context->getCart());
    }

    public function testReturnsCartUntouchedWhenCartHasNoCustomer(): void
    {
        $cart = $this->createCart();
        $cart->method('getCustomer')->willReturn(null);
        $cart->expects(self::never())->method('setShippingAddress');

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        self::assertSame($cart, $this->context->getCart());
    }

    public function testReturnsCartUntouchedWhenCustomerHasNoDefaultAddress(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getDefaultAddress')->willReturn(null);

        $cart = $this->createCart();
        $cart->method('getCustomer')->willReturn($customer);
        $cart->expects(self::never())->method('setShippingAddress');

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        self::assertSame($cart, $this->context->getCart());
    }

    public function testSetsClonedDefaultAddressDetachedFromCustomerAsShippingAddress(): void
    {
        $defaultAddress = new Address();
        $defaultAddress->setCustomer($this->createMock(CustomerInterface::class));

        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getDefaultAddress')->willReturn($defaultAddress);

        $capturedAddress = null;

        $cart = $this->createCart();
        $cart->method('getCustomer')->willReturn($customer);
        $cart->expects(self::once())
            ->method('setShippingAddress')
            ->willReturnCallback(static function (AddressInterface $address) use (&$capturedAddress): void {
                $capturedAddress = $address;
            });

        $this->checkoutEnabledChecker->method('isEnabled')->willReturn(true);

        self::assertSame($cart, $this->context->getCart());
        self::assertInstanceOf(AddressInterface::class, $capturedAddress);
        self::assertNotSame($defaultAddress, $capturedAddress);
        self::assertNull($capturedAddress->getCustomer());
        self::assertNotNull($defaultAddress->getCustomer());
    }

    public function testResetDelegatesToDecoratedContext(): void
    {
        $this->decoratedContext->expects(self::once())->method('reset');

        $this->context->reset();
    }

    private function createCart(): OrderInterface&MockObject
    {
        $cart = $this->createMock(OrderInterface::class);
        $cart->method('getChannel')->willReturn($this->createMock(ChannelInterface::class));

        $this->decoratedContext->method('getCart')->willReturn($cart);

        return $cart;
    }
}
