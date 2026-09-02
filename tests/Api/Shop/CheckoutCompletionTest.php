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

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Api\JsonApiTestCase;

final class CheckoutCompletionTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowCompletingWhenCheckoutStateIsNotCart(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'CHECKOUT_COMPLETION_TOKEN');

        $body = [
            'email' => 'shop@example.com',
            'shippingAddress' => $this->address(),
            'billingAddress' => $this->address(),
            'shipments' => [['methodCode' => 'UPS']],
            'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
        ];

        $this->requestPatch(uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue), body: $body);

        /** @var array{hash: string} $previewContent */
        $previewContent = json_decode((string) $this->client()->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');
        $order = $orderRepository->findCartByTokenValue($tokenValue);
        self::assertInstanceOf(OrderInterface::class, $order);

        $order->setCheckoutState(OrderCheckoutStates::STATE_ADDRESSED);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $entityManager->flush();

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: $body + ['hash' => $previewContent['hash']],
        );

        self::assertSame(422, $this->client()->getResponse()->getStatusCode());
        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => 'Cannot complete as order is in a wrong state. Current: addressed. Possible transitions: skip_shipping.'],
        ]);
    }

    public function testAllowsCompletingWhenCheckoutStateIsCart(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'CHECKOUT_COMPLETION_TOKEN');

        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');

        $body = [
            'email' => 'shop@example.com',
            'shippingAddress' => $this->address(),
            'billingAddress' => $this->address(),
            'shipments' => [['methodCode' => 'UPS']],
            'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
            'notes' => 'Please deliver in the evening',
        ];

        $this->requestPatch(uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue), body: $body);

        /** @var array{hash: string} $previewContent */
        $previewContent = json_decode((string) $this->client()->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: $body + ['hash' => $previewContent['hash']],
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode());

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $entityManager->clear();

        $completedOrder = $orderRepository->findOneByTokenValue($tokenValue);
        self::assertInstanceOf(OrderInterface::class, $completedOrder);

        self::assertSame('Please deliver in the evening', $completedOrder->getNotes());
    }

    /** @return array<string, string> */
    private function address(): array
    {
        return [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'city' => 'New York',
            'street' => 'Avenue',
            'postcode' => '90000',
            'countryCode' => 'US',
        ];
    }
}
