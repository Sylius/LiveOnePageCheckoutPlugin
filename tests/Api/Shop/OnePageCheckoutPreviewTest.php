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

final class OnePageCheckoutPreviewTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testPreviewsOnePageCheckoutForAGuestCart(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ONE_PAGE_TOKEN', quantity: 2);

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'email' => 'guest@example.com',
                'shippingAddress' => [
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
            ],
        );

        $this->assertResponse($this->client()->getResponse(), 'shop/one_page_checkout/preview');
    }

    public function testPreviewsForGuestWithEmptyRequest(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ONE_PAGE_TOKEN', quantity: 2);

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [],
        );

        $this->assertResponse($this->client()->getResponse(), 'shop/one_page_checkout/empty_request');
    }

    public function testPreviewsForLoggedInCustomerWithoutDefaultAddress(): void
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

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ONE_PAGE_TOKEN', quantity: 2);

        $token = $this->generateJwtTokenForShopUserEmail('john@example.com');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [],
            headers: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
        );

        $this->assertResponse($this->client()->getResponse(), 'shop/one_page_checkout/logged_in_without_default_address');
    }

    public function testPreviewsForLoggedInCustomerWithDefaultAddress(): void
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

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ONE_PAGE_TOKEN', quantity: 2);

        $token = $this->generateJwtTokenForShopUserEmail('jane@example.com');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [],
            headers: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
        );

        $this->assertResponse($this->client()->getResponse(), 'shop/one_page_checkout/logged_in_with_default_address');
    }

    public function testReturnsOnlyUsShippingMethodForUsAddress(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ONE_PAGE_TOKEN', quantity: 2);

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'shippingAddress' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'city' => 'New York',
                    'street' => 'Avenue',
                    'postcode' => '90000',
                    'countryCode' => 'US',
                ],
            ],
        );

        $this->assertResponse($this->client()->getResponse(), 'shop/one_page_checkout/us_address_methods');
    }

    public function testReturnsOnlyEuShippingMethodForGermanAddress(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ONE_PAGE_TOKEN', quantity: 2);

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'shippingAddress' => [
                    'firstName' => 'Hans',
                    'lastName' => 'Mueller',
                    'city' => 'Berlin',
                    'street' => 'Hauptstrasse',
                    'postcode' => '10115',
                    'countryCode' => 'DE',
                ],
            ],
        );

        $this->assertResponse($this->client()->getResponse(), 'shop/one_page_checkout/de_address_methods');
    }

    public function testClearsSelectedShippingMethodWhenCountryMakesItIneligible(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ONE_PAGE_TOKEN', quantity: 2);

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'shippingAddress' => [
                    'firstName' => 'Hans',
                    'lastName' => 'Mueller',
                    'city' => 'Berlin',
                    'street' => 'Hauptstrasse',
                    'postcode' => '10115',
                    'countryCode' => 'DE',
                ],
                'shipments' => [
                    ['methodCode' => 'UPS'],
                ],
            ],
        );

        $this->assertResponse($this->client()->getResponse(), 'shop/one_page_checkout/ineligible_method_selected');
    }
}
