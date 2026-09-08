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

final class ItemAvailabilityTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowCompletingWithInsufficientStock(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'product_out_of_stock.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ITEM_AVAILABILITY_TOKEN', productVariantCode: 'OUT_OF_STOCK_VARIANT');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'shipments' => [['methodCode' => 'UPS']],
                'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
                'hash' => 'PREVIEW_HASH',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => 'The product variant with Out Of Stock Variant name does not have sufficient stock.'],
        ]);
    }

    public function testAllowsCompletingWithSufficientStock(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'ITEM_AVAILABILITY_TOKEN');

        $body = [
            'email' => 'shop@example.com',
            'shippingAddress' => $this->address(),
            'billingAddress' => $this->address(),
            'shipments' => [
                ['methodCode' => 'UPS'],
            ],
            'payments' => [
                ['methodCode' => 'CASH_ON_DELIVERY'],
            ],
        ];

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: $body,
        );

        /** @var array{hash: string} $previewContent */
        $previewContent = json_decode((string) $this->client()->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: $body + ['hash' => $previewContent['hash']],
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode());
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
