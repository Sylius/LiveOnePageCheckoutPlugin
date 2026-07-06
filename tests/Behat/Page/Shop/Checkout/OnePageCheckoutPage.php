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

use Sylius\Behat\Page\Shop\Page;
use Webmozart\Assert\Assert;

class OnePageCheckoutPage extends Page implements OnePageCheckoutPageInterface
{
    public function getRouteName(): string
    {
        return 'sylius_live_one_page_checkout_shop_checkout';
    }

    public function specifyEmail(string $email): void
    {
        $this->getElement('email')->setValue($email);
    }

    public function specifyShippingAddress(string $fullName, string $street, string $city, string $postcode, string $countryName): void
    {
        [$firstName, $lastName] = array_pad(explode(' ', $fullName, 2), 2, '');

        $this->getElement('shipping_first_name')->setValue($firstName);
        $this->getElement('shipping_last_name')->setValue($lastName);
        $this->getElement('shipping_street')->setValue($street);
        $this->getElement('shipping_city')->setValue($city);
        $this->getElement('shipping_postcode')->setValue($postcode);
        $this->getElement('shipping_country')->selectOption($countryName);

        $this->waitForFormToUpdate();
    }

    public function specifyShippingProvince(string $province): void
    {
        $this->waitForFormToUpdate();

        if ($this->hasElement('shipping_province_code')) {
            $this->getElement('shipping_province_code')->selectOption($province);
        } else {
            $this->getElement('shipping_province_name')->setValue($province);
        }

        $this->waitForFormToUpdate();
    }

    public function useDifferentBillingAddress(): void
    {
        $this->getElement('different_billing_address')->check();

        $this->waitForFormToUpdate();
    }

    public function specifyBillingAddress(string $fullName, string $street, string $city, string $postcode, string $countryName): void
    {
        [$firstName, $lastName] = array_pad(explode(' ', $fullName, 2), 2, '');

        $this->getElement('billing_first_name')->setValue($firstName);
        $this->getElement('billing_last_name')->setValue($lastName);
        $this->getElement('billing_street')->setValue($street);
        $this->getElement('billing_city')->setValue($city);
        $this->getElement('billing_postcode')->setValue($postcode);
        $this->getElement('billing_country')->selectOption($countryName);

        $this->waitForFormToUpdate();
    }

    public function useNewShippingAddress(): void
    {
        $this->getElement('new_shipping_address')->check();

        $this->waitForFormToUpdate();
    }

    public function selectShippingAddressFromAddressBook(string $fullName): void
    {
        $this->getElement('shipping_address_book')->selectOption($fullName);

        $this->waitForFormToUpdate();
    }

    public function selectShippingMethod(string $shippingMethodName): void
    {
        $radio = $this->getElement('shipping_method', ['%name%' => $shippingMethodName])->find('css', 'input[type="radio"]');
        Assert::notNull($radio);
        $radio->click();

        $this->waitForFormToUpdate();
    }

    public function selectPaymentMethod(string $paymentMethodName): void
    {
        $radio = $this->getElement('payment_method', ['%name%' => $paymentMethodName])->find('css', 'input[type="radio"]');
        Assert::notNull($radio);
        $radio->click();

        $this->waitForFormToUpdate();
    }

    public function applyCoupon(string $couponCode): void
    {
        $this->getElement('coupon_input')->setValue($couponCode);
        $this->getElement('apply_coupon')->press();

        $this->waitForFormToUpdate();
    }

    public function removeCoupon(): void
    {
        $this->getElement('remove_coupon')->click();

        $this->waitForFormToUpdate();
    }

    public function pay(): void
    {
        $this->getElement('pay')->press();
    }

    public function hasFlashMessage(string $message): bool
    {
        return $this->hasElement('flash_message') &&
            str_contains($this->getElement('flash_message')->getText(), $message);
    }

    public function hasValidationError(string $message): bool
    {
        return $this->hasElement('validation_error') &&
            str_contains($this->getElement('validation_error')->getText(), $message);
    }

    public function hasPromotionDiscount(): bool
    {
        return $this->hasElement('promotion_discount');
    }

    public function hasAppliedCoupon(string $couponCode): bool
    {
        return $this->hasElement('applied_coupon') &&
            str_contains($this->getElement('applied_coupon')->getText(), $couponCode);
    }

    public function hasShippingMethod(string $shippingMethodName): bool
    {
        return $this->hasElement('shipping_method', ['%name%' => $shippingMethodName]);
    }

    public function hasSelectedShippingMethod(string $shippingMethodName): bool
    {
        if (!$this->hasElement('shipping_method', ['%name%' => $shippingMethodName])) {
            return false;
        }

        $radio = $this->getElement('shipping_method', ['%name%' => $shippingMethodName])->find('css', 'input[type="radio"]');

        return null !== $radio && $radio->isChecked();
    }

    public function hasPaymentMethod(string $paymentMethodName): bool
    {
        return $this->hasElement('payment_method', ['%name%' => $paymentMethodName]);
    }

    public function waitForFormToUpdate(): void
    {
        $this->waitForElementUpdate('form');
    }

    /**
     * @return array<string, string>
     */
    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'applied_coupon' => '[data-test-applied-coupon]',
            'apply_coupon' => '[data-test-apply-coupon]',
            'billing_city' => '[data-test-billing-address] [data-test-city]',
            'billing_country' => '[data-test-billing-address] [data-test-country]',
            'billing_first_name' => '[data-test-billing-address] [data-test-first-name]',
            'billing_last_name' => '[data-test-billing-address] [data-test-last-name]',
            'billing_postcode' => '[data-test-billing-address] [data-test-postcode]',
            'billing_street' => '[data-test-billing-address] [data-test-street]',
            'coupon_input' => '[data-test-coupon] input',
            'different_billing_address' => '[data-test-different-billing-address]',
            'email' => '[data-test-email]',
            'flash_message' => '[data-test-sylius-flash-message]',
            'form' => '[data-live-name-value="sylius_live_one_page_checkout:checkout:form"]',
            'new_shipping_address' => '[data-test-shipping-address] [data-test-new-address]',
            'pay' => '[data-test-pay]',
            'payment_method' => '[data-test-payment-method="%name%"]',
            'promotion_discount' => '[data-test-promotion-discount]',
            'remove_coupon' => '[data-test-remove-coupon]',
            'shipping_address_book' => '[data-test-shipping-address] [data-test-address-book]',
            'shipping_city' => '[data-test-shipping-address] [data-test-city]',
            'shipping_country' => '[data-test-shipping-address] [data-test-country]',
            'shipping_first_name' => '[data-test-shipping-address] [data-test-first-name]',
            'shipping_last_name' => '[data-test-shipping-address] [data-test-last-name]',
            'shipping_method' => '[data-test-shipping-method="%name%"]',
            'shipping_postcode' => '[data-test-shipping-address] [data-test-postcode]',
            'shipping_province_code' => '[data-test-shipping-address] [data-test-province-code]',
            'shipping_province_name' => '[data-test-shipping-address] [data-test-province-name]',
            'shipping_street' => '[data-test-shipping-address] [data-test-street]',
            'validation_error' => '[data-test-validation-error]',
        ]);
    }
}
