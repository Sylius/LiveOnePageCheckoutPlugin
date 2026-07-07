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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Behat\Page\Shop\Checkout;

use Sylius\Behat\Page\Shop\PageInterface;

interface OnePageCheckoutPageInterface extends PageInterface
{
    public function specifyEmail(string $email): void;

    public function specifyShippingAddress(string $fullName, string $street, string $city, string $postcode, string $countryName): void;

    public function specifyShippingProvince(string $province): void;

    public function useDifferentBillingAddress(): void;

    public function specifyBillingAddress(string $fullName, string $street, string $city, string $postcode, string $countryName): void;

    public function useNewShippingAddress(): void;

    public function useNewBillingAddress(): void;

    public function selectShippingAddressFromAddressBook(string $fullName): void;

    public function selectShippingMethod(string $shippingMethodName): void;

    public function selectPaymentMethod(string $paymentMethodName): void;

    public function applyCoupon(string $couponCode): void;

    public function removeCoupon(): void;

    public function pay(): void;

    public function hasShippingMethod(string $shippingMethodName): bool;

    public function hasNoShippingMethodsMessage(): bool;

    public function hasSelectedShippingMethod(string $shippingMethodName): bool;

    public function hasFlashMessage(string $message): bool;

    public function hasValidationError(string $message): bool;

    public function hasPromotionDiscount(): bool;

    public function hasAppliedCoupon(string $couponCode): bool;

    public function hasPaymentMethod(string $paymentMethodName): bool;

    public function waitForFormToUpdate(): void;
}
