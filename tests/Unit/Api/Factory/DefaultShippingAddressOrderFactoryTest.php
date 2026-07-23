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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Factory\OrderFactoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\DefaultShippingAddressOrderFactory;
use Sylius\LiveOnePageCheckoutPlugin\Applier\DefaultShippingAddressApplierInterface;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Channel;

final class DefaultShippingAddressOrderFactoryTest extends TestCase
{
    /** @var OrderFactoryInterface<OrderInterface>&MockObject */
    private OrderFactoryInterface&MockObject $decoratedFactory;

    private DefaultShippingAddressApplierInterface&MockObject $defaultShippingAddressApplier;

    private DefaultShippingAddressOrderFactory $factory;

    protected function setUp(): void
    {
        $this->decoratedFactory = $this->createMock(OrderFactoryInterface::class);
        $this->defaultShippingAddressApplier = $this->createMock(DefaultShippingAddressApplierInterface::class);

        $this->factory = new DefaultShippingAddressOrderFactory(
            $this->decoratedFactory,
            $this->defaultShippingAddressApplier,
        );
    }

    public function testCreateNewDelegatesToDecoratedFactory(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $this->decoratedFactory->expects(self::once())->method('createNew')->willReturn($order);

        self::assertSame($order, $this->factory->createNew());
    }

    public function testCreateNewCartAppliesDefaultShippingAddressToCartFromDecoratedFactory(): void
    {
        $channel = new Channel();
        $cart = $this->createMock(OrderInterface::class);

        $this->decoratedFactory->method('createNewCart')->willReturn($cart);

        $this->defaultShippingAddressApplier->expects(self::once())
            ->method('apply')
            ->with($cart, $channel);

        self::assertSame($cart, $this->factory->createNewCart($channel, null, 'en_US'));
    }
}
