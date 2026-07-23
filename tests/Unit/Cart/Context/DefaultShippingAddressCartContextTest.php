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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\ResettableCartContextInterface;
use Sylius\LiveOnePageCheckoutPlugin\Applier\DefaultShippingAddressApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Cart\Context\DefaultShippingAddressCartContext;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Channel;

final class DefaultShippingAddressCartContextTest extends TestCase
{
    private ResettableCartContextInterface&MockObject $decoratedContext;

    private DefaultShippingAddressApplierInterface&MockObject $defaultShippingAddressApplier;

    private DefaultShippingAddressCartContext $context;

    protected function setUp(): void
    {
        $this->decoratedContext = $this->createMock(ResettableCartContextInterface::class);
        $this->defaultShippingAddressApplier = $this->createMock(DefaultShippingAddressApplierInterface::class);

        $this->context = new DefaultShippingAddressCartContext(
            $this->decoratedContext,
            $this->defaultShippingAddressApplier,
        );
    }

    public function testAppliesDefaultShippingAddressToCartFromDecoratedContext(): void
    {
        $channel = new Channel();
        $cart = $this->createMock(OrderInterface::class);
        $cart->method('getChannel')->willReturn($channel);

        $this->decoratedContext->method('getCart')->willReturn($cart);

        $this->defaultShippingAddressApplier->expects(self::once())
            ->method('apply')
            ->with($cart, self::isInstanceOf(ChannelInterface::class));

        self::assertSame($cart, $this->context->getCart());
    }

    public function testResetDelegatesToDecoratedContext(): void
    {
        $this->decoratedContext->expects(self::once())->method('reset');

        $this->context->reset();
    }
}
