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
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\AddressPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\CheckoutPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\ItemPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\OrderSummary;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\PaymentPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\ShipmentPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\AddressPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\CheckoutPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\CheckoutPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ItemsPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\OrderSummaryFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShipmentPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasherInterface;

final class CheckoutPreviewFactoryTest extends TestCase
{
    private OrderSummaryFactoryInterface&MockObject $orderSummaryFactory;

    private ItemsPreviewFactoryInterface&MockObject $itemPreviewFactory;

    private ShipmentPreviewFactoryInterface&MockObject $shipmentPreviewFactory;

    private PaymentPreviewFactoryInterface&MockObject $paymentPreviewFactory;

    private OrderHasherInterface&MockObject $orderHasher;

    private AddressPreviewFactoryInterface&MockObject $addressPreviewFactory;

    private CheckoutPreviewFactory $factory;

    protected function setUp(): void
    {
        $this->orderSummaryFactory = $this->createMock(OrderSummaryFactoryInterface::class);
        $this->itemPreviewFactory = $this->createMock(ItemsPreviewFactoryInterface::class);
        $this->shipmentPreviewFactory = $this->createMock(ShipmentPreviewFactoryInterface::class);
        $this->paymentPreviewFactory = $this->createMock(PaymentPreviewFactoryInterface::class);
        $this->orderHasher = $this->createMock(OrderHasherInterface::class);
        $this->addressPreviewFactory = $this->createMock(AddressPreviewFactoryInterface::class);

        $this->factory = new CheckoutPreviewFactory(
            $this->orderSummaryFactory,
            $this->itemPreviewFactory,
            $this->shipmentPreviewFactory,
            $this->paymentPreviewFactory,
            $this->orderHasher,
            $this->addressPreviewFactory,
        );
    }

    public function testImplementsCheckoutPreviewFactoryInterface(): void
    {
        self::assertInstanceOf(CheckoutPreviewFactoryInterface::class, $this->factory);
    }

    public function testCreatesCheckoutPreviewComposingAllFactories(): void
    {
        $billingAddress = $this->createMock(AddressInterface::class);
        $shippingAddress = $this->createMock(AddressInterface::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getBillingAddress')->willReturn($billingAddress);
        $order->method('getShippingAddress')->willReturn($shippingAddress);

        $orderSummary = $this->createMock(OrderSummary::class);
        $itemPreviews = [
            $this->createMock(ItemPreview::class),
        ];
        $shipmentPreviews = [
            $this->createMock(ShipmentPreview::class),
            $this->createMock(ShipmentPreview::class),
        ];
        $paymentPreviews = [
            $this->createMock(PaymentPreview::class),
        ];
        $hash = 'abc123def456';
        $billingAddressPreview = $this->createMock(AddressPreview::class);
        $shippingAddressPreview = $this->createMock(AddressPreview::class);

        $this->orderSummaryFactory->expects(self::once())
            ->method('create')
            ->with($order)
            ->willReturn($orderSummary);

        $this->itemPreviewFactory->expects(self::once())
            ->method('create')
            ->with($order)
            ->willReturn($itemPreviews);

        $this->shipmentPreviewFactory->expects(self::once())
            ->method('create')
            ->with($order)
            ->willReturn($shipmentPreviews);

        $this->paymentPreviewFactory->expects(self::once())
            ->method('create')
            ->with($order)
            ->willReturn($paymentPreviews);

        $this->orderHasher->expects(self::once())
            ->method('hash')
            ->with($order)
            ->willReturn($hash);

        $this->addressPreviewFactory->method('create')
            ->willReturnMap([
                [$billingAddress, $billingAddressPreview],
                [$shippingAddress, $shippingAddressPreview],
            ]);

        $result = $this->factory->create($order);

        self::assertInstanceOf(CheckoutPreview::class, $result);
        self::assertSame($orderSummary, $result->summary);
        self::assertSame($itemPreviews, $result->items);
        self::assertSame($shipmentPreviews, $result->shipments);
        self::assertSame($paymentPreviews, $result->payments);
        self::assertSame($hash, $result->hash);
        self::assertSame($billingAddressPreview, $result->billingAddress);
        self::assertSame($shippingAddressPreview, $result->shippingAddress);
    }

    public function testCreatesCheckoutPreviewWithEmptyShipmentsAndPayments(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getBillingAddress')->willReturn(null);
        $order->method('getShippingAddress')->willReturn(null);

        $orderSummary = $this->createMock(OrderSummary::class);
        $hash = 'hash123';

        $this->orderSummaryFactory->method('create')->willReturn($orderSummary);
        $this->itemPreviewFactory->method('create')->willReturn([]);
        $this->shipmentPreviewFactory->method('create')->willReturn([]);
        $this->paymentPreviewFactory->method('create')->willReturn([]);
        $this->orderHasher->method('hash')->willReturn($hash);
        $this->addressPreviewFactory->method('create')->willReturn(null);

        $result = $this->factory->create($order);

        self::assertCount(0, $result->items);
        self::assertCount(0, $result->shipments);
        self::assertCount(0, $result->payments);
        self::assertNull($result->billingAddress);
        self::assertNull($result->shippingAddress);
    }

    public function testCallsAllFactoriesInCorrectOrder(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getBillingAddress')->willReturn(null);
        $order->method('getShippingAddress')->willReturn(null);
        $calls = [];

        $this->orderSummaryFactory->method('create')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'orderSummaryFactory';

            return $this->createMock(OrderSummary::class);
        });

        $this->itemPreviewFactory->method('create')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'itemPreviewFactory';

            return [];
        });

        $this->shipmentPreviewFactory->method('create')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'shipmentPreviewFactory';

            return [];
        });

        $this->paymentPreviewFactory->method('create')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'paymentPreviewFactory';

            return [];
        });

        $this->orderHasher->method('hash')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'orderHasher';

            return 'hash';
        });

        $this->addressPreviewFactory->method('create')->willReturn(null);

        $this->factory->create($order);

        self::assertCount(5, $calls);
    }
}
