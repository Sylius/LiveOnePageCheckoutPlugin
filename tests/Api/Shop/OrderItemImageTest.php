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

final class OrderItemImageTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testAddsImageToOrderItemsWhenOnePageCheckoutIsEnabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ORDER_ITEM_IMAGE_TOKEN');

        $this->requestGet(sprintf('/api/v2/shop/orders/%s', $tokenValue));

        $response = $this->client()->getResponse();
        self::assertResponseIsSuccessful();

        /** @var array{items: list<array<string, mixed>>} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('image', $content['items'][0]);
    }

    public function testDoesNotAddImageToOrderItemsWhenOnePageCheckoutIsDisabled(): void
    {
        $this->loadFixturesFromFiles([
            'channel_disabled.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ORDER_ITEM_IMAGE_TOKEN');

        $this->requestGet(sprintf('/api/v2/shop/orders/%s', $tokenValue));

        $response = $this->client()->getResponse();
        self::assertResponseIsSuccessful();

        /** @var array{items: list<array<string, mixed>>} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('image', $content['items'][0]);
    }
}
