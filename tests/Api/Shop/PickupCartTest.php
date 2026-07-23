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

final class PickupCartTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testPrefillsShippingAddressFromCustomerDefaultAddressWhenOnePageCheckoutIsEnabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'customer.yaml',
            'shop_user.yaml',
        ]);

        $token = $this->generateJwtTokenForShopUserEmail('jane@example.com');

        $this->requestPost(
            uri: '/api/v2/shop/orders',
            body: [],
            headers: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
        );

        $response = $this->client()->getResponse();
        self::assertSame(201, $response->getStatusCode());

        /** @var array{shippingAddress: array<string, mixed>|null} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertNotNull($content['shippingAddress']);
        self::assertSame('Jane', $content['shippingAddress']['firstName']);
        self::assertSame('Smith', $content['shippingAddress']['lastName']);
        self::assertSame('123 Main Street', $content['shippingAddress']['street']);
        self::assertSame('New York', $content['shippingAddress']['city']);
        self::assertSame('10001', $content['shippingAddress']['postcode']);
        self::assertSame('US', $content['shippingAddress']['countryCode']);
    }

    public function testDoesNotPrefillShippingAddressWhenOnePageCheckoutIsDisabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel_disabled.yaml',
            'country.yaml',
            'customer.yaml',
            'shop_user.yaml',
        ]);

        $token = $this->generateJwtTokenForShopUserEmail('jane@example.com');

        $this->requestPost(
            uri: '/api/v2/shop/orders',
            body: [],
            headers: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
        );

        $response = $this->client()->getResponse();
        self::assertSame(201, $response->getStatusCode());

        /** @var array{shippingAddress: array<string, mixed>|null} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertNull($content['shippingAddress']);
    }
}
