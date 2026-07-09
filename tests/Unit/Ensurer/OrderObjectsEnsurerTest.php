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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Ensurer;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Factory\AddressFactoryInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Payment\Factory\PaymentFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Ensurer\OrderObjectsEnsurer;
use Sylius\LiveOnePageCheckoutPlugin\Ensurer\OrderObjectsEnsurerInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class OrderObjectsEnsurerTest extends TestCase
{
    /** @var AddressFactoryInterface<AddressInterface>&MockObject */
    private AddressFactoryInterface&MockObject $addressFactory;

    /** @var FactoryInterface<ShipmentInterface>&MockObject */
    private FactoryInterface&MockObject $shipmentFactory;

    /** @var PaymentFactoryInterface<PaymentInterface>&MockObject */
    private PaymentFactoryInterface&MockObject $paymentFactory;

    private OrderObjectsEnsurer $ensurer;

    protected function setUp(): void
    {
        $this->addressFactory = $this->createMock(AddressFactoryInterface::class);
        $this->shipmentFactory = $this->createMock(FactoryInterface::class);
        $this->paymentFactory = $this->createMock(PaymentFactoryInterface::class);

        $this->ensurer = new OrderObjectsEnsurer(
            $this->addressFactory,
            $this->shipmentFactory,
            $this->paymentFactory,
        );
    }

    public function testImplementsInterface(): void
    {
        self::assertInstanceOf(OrderObjectsEnsurerInterface::class, $this->ensurer);
    }

    public function testCreatesMissingAddresses(): void
    {
        $address = $this->createMock(AddressInterface::class);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShippingAddress')->willReturn(null);
        $order->method('getBillingAddress')->willReturn(null);
        $order->method('isShippingRequired')->willReturn(false);
        $order->method('getCurrencyCode')->willReturn(null);

        $this->addressFactory->method('createNew')->willReturn($address);

        $order->expects(self::once())->method('setShippingAddress')->with($address);
        $order->expects(self::once())->method('setBillingAddress')->with($address);

        $this->ensurer->ensure($order);
    }

    public function testDoesNotOverwriteExistingAddresses(): void
    {
        $order = $this->createOrder(
            shippingAddress: $this->createMock(AddressInterface::class),
            billingAddress: $this->createMock(AddressInterface::class),
        );

        $this->addressFactory->expects(self::never())->method('createNew');
        $order->expects(self::never())->method('setShippingAddress');
        $order->expects(self::never())->method('setBillingAddress');

        $this->ensurer->ensure($order);
    }

    public function testAddsShipmentWhenShippingRequiredAndNoShipments(): void
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $order = $this->createOrder(shippingRequired: true, shipments: []);

        $this->shipmentFactory->method('createNew')->willReturn($shipment);

        $order->expects(self::once())->method('addShipment')->with($shipment);

        $this->ensurer->ensure($order);
    }

    public function testDoesNotAddShipmentWhenShippingNotRequired(): void
    {
        $order = $this->createOrder(shippingRequired: false);

        $this->shipmentFactory->expects(self::never())->method('createNew');
        $order->expects(self::never())->method('addShipment');

        $this->ensurer->ensure($order);
    }

    public function testDoesNotAddShipmentWhenShipmentAlreadyPresent(): void
    {
        $order = $this->createOrder(
            shippingRequired: true,
            shipments: [$this->createMock(ShipmentInterface::class)],
        );

        $this->shipmentFactory->expects(self::never())->method('createNew');
        $order->expects(self::never())->method('addShipment');

        $this->ensurer->ensure($order);
    }

    public function testAddsPaymentWhenCurrencySetAndNoPayments(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $order = $this->createOrder(currencyCode: 'USD', total: 1500, payments: []);

        $this->paymentFactory
            ->expects(self::once())
            ->method('createWithAmountAndCurrencyCode')
            ->with(1500, 'USD')
            ->willReturn($payment)
        ;
        $order->expects(self::once())->method('addPayment')->with($payment);

        $this->ensurer->ensure($order);
    }

    public function testDoesNotAddPaymentWhenPaymentAlreadyPresent(): void
    {
        $order = $this->createOrder(currencyCode: 'USD', payments: [$this->createMock(PaymentInterface::class)]);

        $this->paymentFactory->expects(self::never())->method('createWithAmountAndCurrencyCode');
        $order->expects(self::never())->method('addPayment');

        $this->ensurer->ensure($order);
    }

    public function testDoesNotAddPaymentWhenCurrencyCodeIsNull(): void
    {
        $order = $this->createOrder(currencyCode: null);

        $this->paymentFactory->expects(self::never())->method('createWithAmountAndCurrencyCode');
        $order->expects(self::never())->method('addPayment');

        $this->ensurer->ensure($order);
    }

    /**
     * @param array<ShipmentInterface> $shipments
     * @param array<PaymentInterface> $payments
     */
    private function createOrder(
        ?AddressInterface $shippingAddress = null,
        ?AddressInterface $billingAddress = null,
        bool $shippingRequired = false,
        array $shipments = [],
        ?string $currencyCode = null,
        int $total = 0,
        array $payments = [],
    ): OrderInterface&MockObject {
        $shippingAddress ??= $this->createMock(AddressInterface::class);
        $billingAddress ??= $this->createMock(AddressInterface::class);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getShippingAddress')->willReturn($shippingAddress);
        $order->method('getBillingAddress')->willReturn($billingAddress);
        $order->method('isShippingRequired')->willReturn($shippingRequired);
        $order->method('getShipments')->willReturn(new ArrayCollection($shipments));
        $order->method('getCurrencyCode')->willReturn($currencyCode);
        $order->method('getTotal')->willReturn($total);
        $order->method('getPayments')->willReturn(new ArrayCollection($payments));

        return $order;
    }
}
