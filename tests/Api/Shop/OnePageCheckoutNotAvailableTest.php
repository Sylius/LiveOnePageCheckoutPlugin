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

final class OnePageCheckoutNotAvailableTest extends JsonApiTestCase
{
    private const NOT_AVAILABLE_MESSAGE = 'This action is not available: one-page checkout is disabled for this channel.';

    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowGuestToPreviewWhenOnePageCheckoutIsDisabled(): void
    {
        $tokenValue = $this->pickUpCartOnDisabledChannel('NOT_AVAILABLE_GUEST_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [],
        );

        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => self::NOT_AVAILABLE_MESSAGE],
        ]);
    }

    public function testDoesNotAllowLoggedInCustomerToPreviewWhenOnePageCheckoutIsDisabled(): void
    {
        $tokenValue = $this->pickUpCartOnDisabledChannel(
            tokenValue: 'NOT_AVAILABLE_LOGGED_IN_TOKEN',
            customerEmail: 'john@example.com',
        );

        $token = $this->generateJwtTokenForShopUserEmail('john@example.com');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [],
            headers: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
        );

        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => self::NOT_AVAILABLE_MESSAGE],
        ]);
    }

    public function testDoesNotAllowGuestToCompleteWhenOnePageCheckoutIsDisabled(): void
    {
        $tokenValue = $this->pickUpCartOnDisabledChannel('NOT_AVAILABLE_COMPLETE_TOKEN');

        $address = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'city' => 'New York',
            'street' => 'Avenue',
            'postcode' => '90000',
            'countryCode' => 'US',
        ];

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $address,
                'billingAddress' => $address,
                'shipments' => [['methodCode' => 'UPS']],
                'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
                'hash' => 'PREVIEW_HASH',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => self::NOT_AVAILABLE_MESSAGE],
            ['propertyPath' => '', 'message' => 'Cannot complete as order is in a wrong state. Current: cart. Possible transitions: address.'],
        ]);
    }

    private function pickUpCartOnDisabledChannel(string $tokenValue, ?string $customerEmail = null): string
    {
        $this->loadFixturesFromFiles([
            'channel_disabled.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'customer.yaml',
            'shop_user.yaml',
        ]);

        return $this->pickUpCartWithItem(tokenValue: $tokenValue, customerEmail: $customerEmail);
    }
}
