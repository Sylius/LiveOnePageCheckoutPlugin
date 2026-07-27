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

final class CountryCodeExistsTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowNonExistentCountryCodeOnPreview(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'COUNTRY_CODE_EXISTS_TOKEN');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'billingAddress' => $this->address(countryCode: 'GB'),
                'shippingAddress' => $this->address(countryCode: 'GB'),
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'billingAddress.countryCode', 'message' => 'The country GB does not exist.'],
            ['propertyPath' => 'shippingAddress.countryCode', 'message' => 'The country GB does not exist.'],
        ]);
    }

    public function testDoesNotAllowNonExistentCountryCodeOnComplete(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'COUNTRY_CODE_EXISTS_TOKEN');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'billingAddress' => $this->address(countryCode: 'GB'),
                'shippingAddress' => $this->address(countryCode: 'GB'),
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'billingAddress.countryCode', 'message' => 'The country GB does not exist.'],
            ['propertyPath' => 'shippingAddress.countryCode', 'message' => 'The country GB does not exist.'],
        ]);
    }

    public function testAllowsPreviewForKnownCountryCode(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'COUNTRY_CODE_EXISTS_TOKEN');

        $this->requestPost(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'billingAddress' => $this->address(countryCode: 'US'),
                'shippingAddress' => $this->address(countryCode: 'US'),
            ],
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode());
    }

    /**
     * @return array<string, string>
     */
    private function address(string $countryCode): array
    {
        return [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'city' => 'New York',
            'street' => 'Avenue',
            'postcode' => '90000',
            'countryCode' => $countryCode,
        ];
    }
}
