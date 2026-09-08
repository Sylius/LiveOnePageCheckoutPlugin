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

final class ShippingTaxTotalTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testReturnsZeroShippingTaxTotalWhenShippingIsNotTaxed(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $summary = $this->previewSummary('SHIPPING_TAX_UNTAXED_TOKEN', 'UPS');

        self::assertSame(500, $summary['shippingTotal']);
        self::assertSame(0, $summary['shippingTaxTotal']);
        self::assertSame(0, $summary['taxTotal']);
    }

    public function testReturnsShippingTaxTotalSeparatelyFromShippingCost(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method_taxed.yaml',
            'payment_method.yaml',
        ]);

        $summary = $this->previewSummary('SHIPPING_TAX_TAXED_TOKEN', 'UPS_TAXED');

        self::assertSame(550, $summary['shippingTotal']);
        self::assertSame(50, $summary['shippingTaxTotal']);
        self::assertSame(50, $summary['taxTotal']);
    }

    public function testReturnsShippingTaxTotalSeparatelyFromShippingCostInTheCompletedOrder(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method_taxed.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = 'SHIPPING_TAX_COMPLETE_TOKEN';
        $this->pickUpCartWithItem(tokenValue: $tokenValue, quantity: 2);

        $body = [
            'email' => 'shop@example.com',
            'shippingAddress' => $this->address(),
            'billingAddress' => $this->address(),
            'shipments' => [['methodCode' => 'UPS_TAXED']],
            'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
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

        $response = $this->client()->getResponse();
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        /** @var array{shippingTotal: int, shippingTaxTotal: int, taxTotal: int} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(550, $content['shippingTotal']);
        self::assertSame(50, $content['shippingTaxTotal']);
        self::assertSame(50, $content['taxTotal']);
    }

    /** @return array<string, mixed> */
    private function previewSummary(string $tokenValue, string $shippingMethodCode): array
    {
        $this->pickUpCartWithItem(tokenValue: $tokenValue, quantity: 2);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'shippingAddress' => $this->address(),
                'shipments' => [['methodCode' => $shippingMethodCode]],
            ],
        );

        $response = $this->client()->getResponse();
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        /** @var array{summary: array<string, mixed>} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return $content['summary'];
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
