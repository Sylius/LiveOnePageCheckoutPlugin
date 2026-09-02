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

    public function testGuestDoesNotAllowReusingOncePerCustomerCouponInPreviewAfterFirstOrderIsPlaced(): void
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

    public function testGuestAllowsDifferentCustomerToUseOncePerCustomerCouponAfterAnotherCustomerAlreadyUsedIt(): void
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

    public function testGuestDoesNotAllowReusingOncePerCustomerCouponOnDirectCompleteWithoutPriorPreview(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'promotion_coupon.yaml',
        ]);

        $email = 'direct-complete-reuse@example.com';

        $this->completeCheckoutWithCoupon(tokenValue: 'ONCE_PER_CUSTOMER_DIRECT_FIRST', email: $email);

        $secondTokenValue = $this->pickUpCartWithItem(tokenValue: 'ONCE_PER_CUSTOMER_DIRECT_SECOND');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $secondTokenValue),
            body: [
                'email' => $email,
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'shipments' => [['methodCode' => 'UPS']],
                'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
                'couponCode' => 'ONCE_PER_CUSTOMER',
                'hash' => 'PREVIEW_HASH',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'couponCode', 'message' => 'Coupon code is invalid.'],
        ]);
    }

    public function testLoggedInCustomerDoesNotAllowReusingOncePerCustomerCouponInPreview(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'promotion_coupon.yaml',
            'customer.yaml',
            'shop_user.yaml',
        ]);

        $email = 'jane@example.com';
        $token = $this->generateJwtTokenForShopUserEmail($email);
        $authHeaders = ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)];

        $firstTokenValue = $this->pickUpCartWithItem(tokenValue: 'ONCE_PER_CUSTOMER_LOGGED_IN_FIRST', customerEmail: $email);

        $body = [
            'email' => $email,
            'shippingAddress' => $this->address(),
            'billingAddress' => $this->address(),
            'shipments' => [['methodCode' => 'UPS']],
            'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
            'couponCode' => 'ONCE_PER_CUSTOMER',
        ];

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $firstTokenValue),
            body: $body,
            headers: $authHeaders,
        );

        /** @var array{hash: string} $previewContent */
        $previewContent = json_decode((string) $this->client()->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $firstTokenValue),
            body: $body + ['hash' => $previewContent['hash']],
            headers: $authHeaders,
        );

        self::assertSame(200, $this->client()->getResponse()->getStatusCode(), (string) $this->client()->getResponse()->getContent());

        $secondTokenValue = $this->pickUpCartWithItem(tokenValue: 'ONCE_PER_CUSTOMER_LOGGED_IN_SECOND', customerEmail: $email);

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $secondTokenValue),
            body: $body,
            headers: $authHeaders,
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'couponCode', 'message' => 'Coupon code is invalid.'],
        ]);
    }

    public function testPreviewReturnsSingleViolationWhenCouponCodeDoesNotExist(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'NONEXISTENT_COUPON_PREVIEW');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
                'couponCode' => 'TOTALLY_BOGUS_CODE',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'couponCode', 'message' => 'Coupon code is invalid.'],
        ]);
    }

    public function testCompleteReturnsSingleViolationWhenCouponCodeDoesNotExist(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'NONEXISTENT_COUPON_COMPLETE');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'bogus-coupon@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
                'shipments' => [['methodCode' => 'UPS']],
                'payments' => [['methodCode' => 'CASH_ON_DELIVERY']],
                'couponCode' => 'TOTALLY_BOGUS_CODE',
                'hash' => 'PREVIEW_HASH',
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => 'couponCode', 'message' => 'Coupon code is invalid.'],
        ]);
    }

    public function testPreviewAllowsOncePerCustomerCouponWhenNoEmailIsProvided(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
            'promotion_coupon.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem(tokenValue: 'NO_EMAIL_ONCE_PER_CUSTOMER');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [
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
