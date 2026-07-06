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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Behat\Page\Shop\Checkout\OnePageCheckoutPageInterface;
use Webmozart\Assert\Assert;

final readonly class CheckoutContext implements Context
{
    public function __construct(
        private OnePageCheckoutPageInterface $onePageCheckoutPage,
    ) {
    }

    #[Then('I should be on the one page checkout')]
    #[Then('I should be redirected to the one page checkout')]
    public function iShouldBeOnTheOnePageCheckout(): void
    {
        Assert::true($this->onePageCheckoutPage->isOpen());
    }

    #[When('I open the one page checkout')]
    public function iOpenTheOnePageCheckout(): void
    {
        $this->onePageCheckoutPage->open();
    }

    #[When('I provide my email as :email')]
    public function iProvideMyEmailAs(string $email): void
    {
        $this->onePageCheckoutPage->specifyEmail($email);
    }

    #[When('I provide my shipping address as :street, :city, :postcode, :country for :customerName')]
    public function iProvideMyShippingAddress(string $street, string $city, string $postcode, string $country, string $customerName): void
    {
        $this->onePageCheckoutPage->specifyShippingAddress($customerName, $street, $city, $postcode, $country);
    }

    #[Then('the :shippingMethodName shipping method should be available')]
    public function theShippingMethodShouldBeAvailable(string $shippingMethodName): void
    {
        Assert::true($this->onePageCheckoutPage->hasShippingMethod($shippingMethodName));
    }

    #[Then('the :shippingMethodName shipping method should not be available')]
    public function theShippingMethodShouldNotBeAvailable(string $shippingMethodName): void
    {
        Assert::false($this->onePageCheckoutPage->hasShippingMethod($shippingMethodName));
    }
}
