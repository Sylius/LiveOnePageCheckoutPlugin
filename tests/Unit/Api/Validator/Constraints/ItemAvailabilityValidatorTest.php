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
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\ItemAvailability;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\ItemAvailabilityValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ItemAvailabilityValidator>
 */
final class ItemAvailabilityValidatorTest extends ConstraintValidatorTestCase
{
    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    private AvailabilityCheckerInterface&MockObject $availabilityChecker;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->availabilityChecker = $this->createMock(AvailabilityCheckerInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ItemAvailabilityValidator($this->orderRepository, $this->availabilityChecker);
    }

    public function testNoViolationWhenOrderNotFound(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');

        $this->orderRepository->method('findCartByTokenValue')->willReturn(null);

        $this->validator->validate($command, new ItemAvailability());

        $this->assertNoViolation();
    }

    public function testViolationWhenStockIsInsufficient(): void
    {
        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('getName')->willReturn('Out Of Stock Variant');

        $orderItem = $this->createMock(OrderItemInterface::class);
        $orderItem->method('getVariant')->willReturn($variant);
        $orderItem->method('getQuantity')->willReturn(5);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([$orderItem]));

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->availabilityChecker->method('isStockSufficient')->with($variant, 5)->willReturn(false);

        $command = new Checkout(orderTokenValue: 'token123');
        $this->validator->validate($command, new ItemAvailability());

        $this->buildViolation('sylius.product_variant.product_variant_with_name_not_sufficient')
            ->setParameter('%productVariantName%', 'Out Of Stock Variant')
            ->assertRaised()
        ;
    }

    public function testNoViolationWhenStockIsSufficient(): void
    {
        $variant = $this->createMock(ProductVariantInterface::class);

        $orderItem = $this->createMock(OrderItemInterface::class);
        $orderItem->method('getVariant')->willReturn($variant);
        $orderItem->method('getQuantity')->willReturn(1);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([$orderItem]));

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->availabilityChecker->method('isStockSufficient')->with($variant, 1)->willReturn(true);

        $command = new Checkout(orderTokenValue: 'token123');
        $this->validator->validate($command, new ItemAvailability());

        $this->assertNoViolation();
    }
}
