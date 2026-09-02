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

final class CheckoutHashTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowCompletingWithoutHash(): void
    {
        $tokenValue = $this->pickUpCartForCheckout('HASH_MISSING_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: $this->checkoutBody(),
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'hash', 'message' => 'The order hash is required. Please preview the checkout first.'],
        ]);
    }

    public function testDoesNotAllowCompletingWithBlankHash(): void
    {
        $tokenValue = $this->pickUpCartForCheckout('HASH_BLANK_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: $this->checkoutBody() + ['hash' => ''],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'hash', 'message' => 'The order hash is required. Please preview the checkout first.'],
        ]);
    }

    public function testDoesNotAllowCompletingWithHashNotMatchingOrderState(): void
    {
        $tokenValue = $this->pickUpCartForCheckout('HASH_MISMATCH_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: $this->checkoutBody() + ['hash' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        );

        $response = $this->client()->getResponse();
        self::assertSame(409, $response->getStatusCode());

        /** @var array{'hydra:description': string} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(
            'Order state has changed since it was previewed. Please refresh checkout data.',
            $content['hydra:description'],
        );
    }

    public function testAllowsCompletingWithHashFromPreview(): void
    {
        $tokenValue = $this->pickUpCartForCheckout('HASH_VALID_TOKEN');

        $body = $this->checkoutBody();

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

    private function pickUpCartForCheckout(string $tokenValue): string
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        return $this->pickUpCartWithItem(tokenValue: $tokenValue);
    }

    /** @return array<string, mixed> */
    private function checkoutBody(): array
    {
        $address = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'city' => 'New York',
            'street' => 'Avenue',
            'postcode' => '90000',
            'countryCode' => 'US',
        ];

        return [
            'email' => 'shop@example.com',
            'shippingAddress' => $address,
            'billingAddress' => $address,
            'shipments' => [['methodCode' => 'UPS']],
            'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
        ];
    }
}
