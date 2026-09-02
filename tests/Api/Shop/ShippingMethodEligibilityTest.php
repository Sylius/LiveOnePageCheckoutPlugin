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

final class ShippingMethodEligibilityTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowCompletingWithoutShippingMethod(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'SHIPPING_ELIGIBILITY_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
                'hash' => 'PREVIEW_HASH',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'shipments', 'message' => 'Please select shipping method.'],
        ]);
    }

    public function testDoesNotAllowCompletingWithNonExistentShippingMethodCode(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'SHIPPING_ELIGIBILITY_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'shipments' => [['methodCode' => 'BOGUS_CODE_XYZ']],
                'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
                'hash' => 'PREVIEW_HASH',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'shipments', 'message' => 'The shipping method with BOGUS_CODE_XYZ code does not exist.'],
        ]);
    }

    public function testDoesNotAllowCompletingWithIneligibleShippingMethod(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'SHIPPING_ELIGIBILITY_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'shipments' => [['methodCode' => 'DHL']],
                'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
                'hash' => 'PREVIEW_HASH',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'shipments[0].method', 'message' => 'Please select shipping method.'],
        ]);
    }

    public function testAllowsCompletingWithEligibleShippingMethod(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'SHIPPING_ELIGIBILITY_TOKEN');

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

    public function testAllowsCompletingWithoutShippingMethodWhenShippingIsNotRequired(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product_digital.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'SHIPPING_ELIGIBILITY_TOKEN', productVariantCode: 'DIGITAL_PRODUCT_VARIANT');

        $body = [
            'email' => 'shop@example.com',
            'shippingAddress' => $this->address(),
            'billingAddress' => $this->address(),
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

    public function testDoesNotAllowCompletingWithoutResubmittingShippingMethodEvenWhenAlreadyPersisted(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'SHIPPING_ELIGIBILITY_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'shipments' => [['methodCode' => 'UPS']],
                'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
            ],
        );

        /** @var array{hash: string} $previewContent */
        $previewContent = json_decode((string) $this->client()->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
                'hash' => $previewContent['hash'],
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'shipments', 'message' => 'Please select shipping method.'],
        ]);
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
