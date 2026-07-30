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

final class PaymentMethodEligibilityTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowCompletingWithoutPaymentMethod(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'PAYMENT_ELIGIBILITY_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'shipments' => [['methodCode' => 'UPS']],
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'payments', 'message' => 'Please select a payment method.'],
        ]);
    }

    public function testDoesNotAllowCompletingWithNonExistentPaymentMethodCode(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'PAYMENT_ELIGIBILITY_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'shipments' => [['methodCode' => 'UPS']],
                'payments' => [['methodCode' => 'BOGUS_CODE_XYZ']],
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'payments', 'message' => 'The payment method with BOGUS_CODE_XYZ code does not exist.'],
        ]);
    }

    public function testDoesNotAllowCompletingWithIneligiblePaymentMethod(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'payment_method_disabled.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'PAYMENT_ELIGIBILITY_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'shipments' => [['methodCode' => 'UPS']],
                'payments' => [['methodCode' => 'DISABLED_METHOD']],
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'payments[0].method', 'message' => 'Please select a payment method.'],
        ]);
    }

    public function testAllowsCompletingWithEligiblePaymentMethod(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'PAYMENT_ELIGIBILITY_TOKEN');

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

    public function testDoesNotAllowCompletingWithoutResubmittingPaymentMethodEvenWhenAlreadyPersisted(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'PAYMENT_ELIGIBILITY_TOKEN');

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
                'shipments' => [['methodCode' => 'UPS']],
                'hash' => $previewContent['hash'],
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'payments', 'message' => 'Please select a payment method.'],
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
