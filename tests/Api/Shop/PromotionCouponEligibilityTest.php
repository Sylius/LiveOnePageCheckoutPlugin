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

final class PromotionCouponEligibilityTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowReusingOncePerCustomerCouponInPreviewAfterFirstOrderIsPlaced(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'promotion_coupon.yaml',
        ]);

        $email = 'coupon-reuse@example.com';

        $this->completeCheckoutWithCoupon(tokenValue: 'ONCE_PER_CUSTOMER_FIRST', email: $email);

        $secondTokenValue = $this->pickUpCartWithItem(tokenValue: 'ONCE_PER_CUSTOMER_SECOND');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $secondTokenValue),
            body: [
                'email' => $email,
                'couponCode' => 'ONCE_PER_CUSTOMER',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'couponCode', 'message' => 'Coupon code is invalid.'],
        ]);
    }

    public function testAllowsDifferentCustomerToUseOncePerCustomerCouponAfterAnotherCustomerAlreadyUsedIt(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'promotion_coupon.yaml',
        ]);

        $this->completeCheckoutWithCoupon(tokenValue: 'ONCE_PER_CUSTOMER_OTHER_FIRST', email: 'first-customer@example.com');

        $secondTokenValue = $this->pickUpCartWithItem(tokenValue: 'ONCE_PER_CUSTOMER_OTHER_SECOND');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $secondTokenValue),
            body: [
                'email' => 'second-customer@example.com',
                'couponCode' => 'ONCE_PER_CUSTOMER',
            ],
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode(), (string) $this->client()->getResponse()->getContent());
    }

    private function completeCheckoutWithCoupon(string $tokenValue, string $email): void
    {
        $this->pickUpCartWithItem(tokenValue: $tokenValue);

        $body = [
            'email' => $email,
            'shippingAddress' => $this->address(),
            'billingAddress' => $this->address(),
            'shipments' => [['methodCode' => 'UPS']],
            'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
            'couponCode' => 'ONCE_PER_CUSTOMER',
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

        self::assertSame(200, $this->client()->getResponse()->getStatusCode(), (string) $this->client()->getResponse()->getContent());
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
