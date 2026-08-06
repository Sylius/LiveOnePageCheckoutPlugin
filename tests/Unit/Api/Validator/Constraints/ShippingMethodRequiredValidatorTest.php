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
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\ShippingMethodRequired;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\ShippingMethodRequiredValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ShippingMethodRequiredValidator>
 */
final class ShippingMethodRequiredValidatorTest extends ConstraintValidatorTestCase
{
    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    /** @var ShippingMethodRepositoryInterface<ShippingMethodInterface>&MockObject */
    private ShippingMethodRepositoryInterface&MockObject $shippingMethodRepository;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->shippingMethodRepository = $this->createMock(ShippingMethodRepositoryInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ShippingMethodRequiredValidator($this->orderRepository, $this->shippingMethodRepository);
    }

    public function testNoViolationWhenOrderNotFound(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');

        $this->orderRepository->method('findCartByTokenValue')->willReturn(null);

        $this->validator->validate($command, new ShippingMethodRequired());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenShippingIsNotRequired(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('isShippingRequired')->willReturn(false);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123', shipments: []);

        $this->shippingMethodRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new ShippingMethodRequired());

        $this->assertNoViolation();
    }

    public function testViolationWhenNoShipmentsProvidedAndShippingIsRequired(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('isShippingRequired')->willReturn(true);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123', shipments: []);

        $this->shippingMethodRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new ShippingMethodRequired());

        $this->buildViolation('sylius.shipment.shipping_method.not_blank')
            ->atPath('property.path.shipments')
            ->assertRaised()
        ;
    }

    public function testViolationWhenShippingMethodCodeIsMissing(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('isShippingRequired')->willReturn(true);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123', shipments: [[]]);

        $this->shippingMethodRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new ShippingMethodRequired());

        $this->buildViolation('sylius.shipment.shipping_method.not_blank')
            ->atPath('property.path.shipments')
            ->assertRaised()
        ;
    }

    public function testViolationWhenShippingMethodCodeIsBlank(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('isShippingRequired')->willReturn(true);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => '  ']]);

        $this->shippingMethodRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new ShippingMethodRequired());

        $this->buildViolation('sylius.shipment.shipping_method.not_blank')
            ->atPath('property.path.shipments')
            ->assertRaised()
        ;
    }

    public function testViolationWhenShippingMethodCodeDoesNotExist(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('isShippingRequired')->willReturn(true);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $this->shippingMethodRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'UNKNOWN'])
            ->willReturn(null)
        ;

        $command = new Checkout(orderTokenValue: 'token123', shipments: [['methodCode' => 'UNKNOWN']]);
        $this->validator->validate($command, new ShippingMethodRequired());

        $this->buildViolation('sylius.shipping_method.not_found')
            ->atPath('property.path.shipments')
            ->setParameter('%code%', 'UNKNOWN')
            ->assertRaised()
        ;
    }

    public function testNoViolationWhenShippingMethodCodeIsProvidedForEveryShipment(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('isShippingRequired')->willReturn(true);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $this->shippingMethodRepository->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturn($this->createMock(ShippingMethodInterface::class))
        ;

        $command = new Checkout(orderTokenValue: 'token123', shipments: [
            ['methodCode' => 'UPS'],
            ['methodCode' => 'DHL'],
        ]);
        $this->validator->validate($command, new ShippingMethodRequired());

        $this->assertNoViolation();
    }
}
