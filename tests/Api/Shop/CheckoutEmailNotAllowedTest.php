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

use Tests\Sylius\LiveOnePageCheckoutPlugin\Api\JsonApiTestCase;

final class CheckoutEmailNotAllowedTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowLoggedInCustomerToSubmitAnotherEmail(): void
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

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'CHECKOUT_EMAIL_TOKEN', customerEmail: 'jane@example.com');
        $token = $this->generateJwtTokenForShopUserEmail('jane@example.com');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'email' => 'another@example.com',
            ],
            headers: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'email', 'message' => 'You cannot place an order using an email address other than your own.'],
        ]);
    }

    public function testAllowsLoggedInCustomerToSubmitOwnEmail(): void
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

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'CHECKOUT_EMAIL_TOKEN', customerEmail: 'jane@example.com');
        $token = $this->generateJwtTokenForShopUserEmail('jane@example.com');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'email' => 'jane@example.com',
            ],
            headers: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode());
    }

    public function testAllowsGuestToSubmitAnyEmailForAlreadyAssignedCart(): void
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

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'CHECKOUT_EMAIL_TOKEN', customerEmail: 'jane@example.com');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'email' => 'another@example.com',
            ],
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode());
    }
}
