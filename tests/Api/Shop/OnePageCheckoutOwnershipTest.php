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

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasherInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Api\JsonApiTestCase;
use Webmozart\Assert\Assert;

final class OnePageCheckoutOwnershipTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotReturnPreviewOfOrderCreatedByAUserWhenAuthenticatedAsAnotherUser(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'customer.yaml',
            'shop_user.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(
            tokenValue: 'OWNERSHIP_TOKEN',
            customerEmail: 'john@example.com',
        );

        $token = $this->generateJwtTokenForShopUserEmail('jane@example.com');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: ['email' => 'john@example.com'],
            headers: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
        );

        $this->assertResponseCode($this->client()->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testDoesNotReturnPreviewOfOrderCreatedByAUserWhenAuthenticatedAsAGuest(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'customer.yaml',
            'shop_user.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(
            tokenValue: 'OWNERSHIP_TOKEN',
            customerEmail: 'john@example.com',
        );

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [],
        );

        $this->assertResponseCode($this->client()->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testDoesNotCompleteOrderCreatedByAUserWhenAuthenticatedAsAnotherUser(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'customer.yaml',
            'shop_user.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(
            tokenValue: 'OWNERSHIP_TOKEN',
            customerEmail: 'john@example.com',
        );

        $token = $this->generateJwtTokenForShopUserEmail('jane@example.com');
        $headers = ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)];

        $hash = $this->computeOrderHash($tokenValue);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: $this->completeCheckoutBody('john@example.com', $hash),
            headers: $headers,
        );

        $this->assertResponseCode($this->client()->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testDoesNotCompleteOrderCreatedByAUserWhenAuthenticatedAsAGuest(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'customer.yaml',
            'shop_user.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(
            tokenValue: 'OWNERSHIP_TOKEN',
            customerEmail: 'john@example.com',
        );

        $hash = $this->computeOrderHash($tokenValue);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: $this->completeCheckoutBody('attacker@example.com', $hash),
        );

        $this->assertResponseCode($this->client()->getResponse(), Response::HTTP_NOT_FOUND);
    }

    private function computeOrderHash(string $tokenValue): string
    {
        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');
        $order = $orderRepository->findCartByTokenValue($tokenValue);
        Assert::isInstanceOf($order, OrderInterface::class);

        /** @var OrderHasherInterface $orderHasher */
        $orderHasher = $this->get('sylius_live_one_page_checkout.hasher.order');

        return $orderHasher->hash($order);
    }

    /**
     * @return array<string, mixed>
     */
    private function completeCheckoutBody(string $email, string $hash): array
    {
        return [
            'email' => $email,
            'hash' => $hash,
            'shippingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'city' => 'New York',
                'street' => 'Avenue',
                'postcode' => '90000',
                'countryCode' => 'US',
            ],
            'billingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'city' => 'New York',
                'street' => 'Avenue',
                'postcode' => '90000',
                'countryCode' => 'US',
            ],
            'shipments' => [
                ['methodCode' => 'UPS'],
            ],
            'payments' => [
                ['methodCode' => 'CASH_ON_DELIVERY'],
            ],
        ];
    }
}
