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

final class CompleteCheckoutEmailTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowBlankEmail(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'COMPLETE_CHECKOUT_EMAIL_TOKEN');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'email', 'message' => 'This value should not be blank.'],
        ]);
    }

    public function testDoesNotAllowInvalidEmailFormat(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'COMPLETE_CHECKOUT_EMAIL_TOKEN');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'not-a-valid-email',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'email', 'message' => 'This email is invalid.'],
        ]);
    }

    public function testDoesNotAllowTooLongEmail(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'COMPLETE_CHECKOUT_EMAIL_TOKEN');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => sprintf('%s@example.com', str_repeat('a', 250)),
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'email', 'message' => 'Email must not be longer than 254 characters.'],
            ['propertyPath' => 'email', 'message' => 'This email is invalid.'],
        ]);
    }
}
