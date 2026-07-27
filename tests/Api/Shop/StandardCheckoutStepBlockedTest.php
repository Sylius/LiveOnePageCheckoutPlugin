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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Api\Shop;

use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Api\JsonApiTestCase;
use Webmozart\Assert\Assert;

final class StandardCheckoutStepBlockedTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testBlocksUpdateCartWhenOnePageCheckoutIsEnabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'STANDARD_CHECKOUT_BLOCKED_TOKEN');

        $this->requestPut(
            uri: sprintf('/api/v2/shop/orders/%s', $tokenValue),
            body: [],
        );

        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => 'This action is not available: one-page checkout is enabled for this channel.'],
        ]);
    }

    public function testDoesNotBlockUpdateCartWhenOnePageCheckoutIsDisabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel_disabled.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'STANDARD_CHECKOUT_BLOCKED_TOKEN');

        $this->requestPut(
            uri: sprintf('/api/v2/shop/orders/%s', $tokenValue),
            body: [],
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode());
    }

    public function testBlocksChooseShippingMethodWhenOnePageCheckoutIsEnabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'STANDARD_CHECKOUT_BLOCKED_TOKEN');
        $this->addressCart($tokenValue);
        $shipmentId = $this->getShipmentId($tokenValue);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/shipments/%d', $tokenValue, $shipmentId),
            body: ['shippingMethod' => 'UPS'],
        );

        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => 'This action is not available: one-page checkout is enabled for this channel.'],
        ]);
    }

    public function testDoesNotBlockChooseShippingMethodWhenOnePageCheckoutIsDisabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel_disabled.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'STANDARD_CHECKOUT_BLOCKED_TOKEN');
        $this->addressCartAndTransition($tokenValue);
        $shipmentId = $this->getShipmentId($tokenValue);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/shipments/%d', $tokenValue, $shipmentId),
            body: ['shippingMethod' => 'UPS'],
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode());
    }

    public function testBlocksChoosePaymentMethodWhenOnePageCheckoutIsEnabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'STANDARD_CHECKOUT_BLOCKED_TOKEN');
        $this->addressCart($tokenValue);
        $paymentId = $this->getPaymentId($tokenValue);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/payments/%d', $tokenValue, $paymentId),
            body: ['paymentMethod' => 'CASH_ON_DELIVERY'],
        );

        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => 'This action is not available: one-page checkout is enabled for this channel.'],
        ]);
    }

    public function testDoesNotBlockChoosePaymentMethodWhenOnePageCheckoutIsDisabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel_disabled.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'STANDARD_CHECKOUT_BLOCKED_TOKEN');
        $this->addressCartAndTransition($tokenValue);
        $this->selectShippingMethod($tokenValue);
        $paymentId = $this->getPaymentId($tokenValue);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/payments/%d', $tokenValue, $paymentId),
            body: ['paymentMethod' => 'CASH_ON_DELIVERY'],
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode());
    }

    private function addressCart(string $tokenValue): void
    {
        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');
        $order = $orderRepository->findCartByTokenValue($tokenValue);
        Assert::isInstanceOf($order, OrderInterface::class);

        $address = new Address();
        $address->setFirstName('John');
        $address->setLastName('Doe');
        $address->setStreet('Main St 1');
        $address->setCity('New York');
        $address->setPostcode('90000');
        $address->setCountryCode('US');

        $order->setShippingAddress($address);
        $order->setBillingAddress(clone $address);

        $this->get('doctrine.orm.entity_manager')->flush();
    }

    private function addressCartAndTransition(string $tokenValue): void
    {
        $this->addressCart($tokenValue);

        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');
        $order = $orderRepository->findCartByTokenValue($tokenValue);
        Assert::isInstanceOf($order, OrderInterface::class);

        /** @var StateMachineInterface $stateMachine */
        $stateMachine = $this->get('sylius_abstraction.state_machine');
        $stateMachine->apply($order, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS);

        $this->get('doctrine.orm.entity_manager')->flush();
    }

    private function selectShippingMethod(string $tokenValue): void
    {
        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');
        $order = $orderRepository->findCartByTokenValue($tokenValue);
        Assert::isInstanceOf($order, OrderInterface::class);

        /** @var ShippingMethodRepositoryInterface<ShippingMethodInterface> $shippingMethodRepository */
        $shippingMethodRepository = $this->get('sylius.repository.shipping_method');
        $shippingMethod = $shippingMethodRepository->findOneBy(['code' => 'UPS']);
        Assert::notNull($shippingMethod);

        /** @var ShipmentInterface $shipment */
        $shipment = $order->getShipments()->first();
        $shipment->setMethod($shippingMethod);

        /** @var StateMachineInterface $stateMachine */
        $stateMachine = $this->get('sylius_abstraction.state_machine');
        $stateMachine->apply($order, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_SELECT_SHIPPING);

        $this->get('doctrine.orm.entity_manager')->flush();
    }

    private function getShipmentId(string $tokenValue): int
    {
        $this->requestGet(sprintf('/api/v2/shop/orders/%s', $tokenValue));

        /** @var array{shipments: array<int, array{id: int}>} $content */
        $content = json_decode((string) $this->client()->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return $content['shipments'][0]['id'];
    }

    private function getPaymentId(string $tokenValue): int
    {
        $this->requestGet(sprintf('/api/v2/shop/orders/%s', $tokenValue));

        /** @var array{payments: array<int, array{id: int}>} $content */
        $content = json_decode((string) $this->client()->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return $content['payments'][0]['id'];
    }
}
