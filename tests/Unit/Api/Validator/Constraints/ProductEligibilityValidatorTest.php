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
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\ProductEligibility;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\ProductEligibilityValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ProductEligibilityValidator>
 */
final class ProductEligibilityValidatorTest extends ConstraintValidatorTestCase
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
        return new ProductEligibilityValidator($this->orderRepository);
    }

    public function testNoViolationWhenOrderNotFound(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');

        $this->orderRepository->method('findCartByTokenValue')->willReturn(null);

        $this->validator->validate($command, new ProductEligibility());

        $this->assertNoViolation();
    }

    public function testViolationWhenVariantIsDisabled(): void
    {
        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('isEnabled')->willReturn(false);
        $variant->method('getName')->willReturn('Disabled Variant');

        $orderItem = $this->createMock(OrderItemInterface::class);
        $orderItem->method('getVariant')->willReturn($variant);

        $channel = $this->createMock(ChannelInterface::class);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([$orderItem]));
        $order->method('getChannel')->willReturn($channel);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123');
        $this->validator->validate($command, new ProductEligibility());

        $this->buildViolation('sylius.order.product_eligibility')
            ->setParameter('%productName%', 'Disabled Variant')
            ->assertRaised()
        ;
    }

    public function testViolationWhenProductIsDisabled(): void
    {
        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('isEnabled')->willReturn(true);

        $product = $this->createMock(ProductInterface::class);
        $product->method('isEnabled')->willReturn(false);
        $product->method('getName')->willReturn('Disabled Product');

        $orderItem = $this->createMock(OrderItemInterface::class);
        $orderItem->method('getVariant')->willReturn($variant);
        $orderItem->method('getProduct')->willReturn($product);

        $channel = $this->createMock(ChannelInterface::class);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([$orderItem]));
        $order->method('getChannel')->willReturn($channel);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123');
        $this->validator->validate($command, new ProductEligibility());

        $this->buildViolation('sylius.order.product_eligibility')
            ->setParameter('%productName%', 'Disabled Product')
            ->assertRaised()
        ;
    }

    public function testViolationWhenProductIsNotInChannel(): void
    {
        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('isEnabled')->willReturn(true);

        $product = $this->createMock(ProductInterface::class);
        $product->method('isEnabled')->willReturn(true);
        $product->method('hasChannel')->willReturn(false);
        $product->method('getName')->willReturn('Not In Channel Product');

        $orderItem = $this->createMock(OrderItemInterface::class);
        $orderItem->method('getVariant')->willReturn($variant);
        $orderItem->method('getProduct')->willReturn($product);

        $channel = $this->createMock(ChannelInterface::class);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([$orderItem]));
        $order->method('getChannel')->willReturn($channel);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123');
        $this->validator->validate($command, new ProductEligibility());

        $this->buildViolation('sylius.order.product_eligibility')
            ->setParameter('%productName%', 'Not In Channel Product')
            ->assertRaised()
        ;
    }

    public function testNoViolationWhenAllItemsAreEligible(): void
    {
        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('isEnabled')->willReturn(true);

        $product = $this->createMock(ProductInterface::class);
        $product->method('isEnabled')->willReturn(true);
        $product->method('hasChannel')->willReturn(true);

        $orderItem = $this->createMock(OrderItemInterface::class);
        $orderItem->method('getVariant')->willReturn($variant);
        $orderItem->method('getProduct')->willReturn($product);

        $channel = $this->createMock(ChannelInterface::class);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([$orderItem]));
        $order->method('getChannel')->willReturn($channel);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $command = new Checkout(orderTokenValue: 'token123');
        $this->validator->validate($command, new ProductEligibility());

        $this->assertNoViolation();
    }
}
