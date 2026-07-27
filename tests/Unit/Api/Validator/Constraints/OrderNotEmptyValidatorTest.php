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

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\OrderNotEmpty;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\OrderNotEmptyValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<OrderNotEmptyValidator>
 */
final class OrderNotEmptyValidatorTest extends ConstraintValidatorTestCase
{
    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new OrderNotEmptyValidator($this->orderRepository);
    }

    public function testNoViolationWhenCartCannotBeFound(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn(null)
        ;

        $this->validator->validate($command, new OrderNotEmpty());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenOrderHasItems(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');
        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([$this->createMock(OrderItemInterface::class)]));

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($order)
        ;

        $this->validator->validate($command, new OrderNotEmpty());

        $this->assertNoViolation();
    }

    public function testViolationWhenOrderHasNoItems(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');
        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection());

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($order)
        ;

        $this->validator->validate($command, new OrderNotEmpty());

        $this->buildViolation('sylius.order.not_empty')
            ->assertRaised()
        ;
    }
}
