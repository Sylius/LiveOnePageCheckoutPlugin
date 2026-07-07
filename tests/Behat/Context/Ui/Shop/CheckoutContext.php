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
use Doctrine\Persistence\ObjectManager;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Promotion\Model\PromotionCouponInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Behat\Page\Shop\Checkout\OnePageCheckoutPageInterface;
use Webmozart\Assert\Assert;

final readonly class CheckoutContext implements Context
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     * @param PromotionCouponRepositoryInterface<PromotionCouponInterface> $couponRepository
     */
    public function __construct(
        private OnePageCheckoutPageInterface $onePageCheckoutPage,
        private OrderRepositoryInterface $orderRepository,
        private PromotionCouponRepositoryInterface $couponRepository,
        private ObjectManager $orderManager,
        private SharedStorageInterface $sharedStorage,
    ) {
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

    #[When('I provide my shipping province as :province')]
    public function iProvideMyShippingProvince(string $province): void
    {
        $this->onePageCheckoutPage->specifyShippingProvince($province);
    }

    #[When('I want to use a new shipping address')]
    public function iWantToUseANewShippingAddress(): void
    {
        $this->onePageCheckoutPage->useNewShippingAddress();
    }

    #[When('I want to use a new billing address')]
    public function iWantToUseANewBillingAddress(): void
    {
        $this->onePageCheckoutPage->useNewBillingAddress();
    }

    #[When('I use a different billing address')]
    public function iUseADifferentBillingAddress(): void
    {
        $this->onePageCheckoutPage->useDifferentBillingAddress();
    }

    #[When('I provide my billing address as :street, :city, :postcode, :country for :customerName')]
    public function iProvideMyBillingAddress(string $street, string $city, string $postcode, string $country, string $customerName): void
    {
        $this->onePageCheckoutPage->specifyBillingAddress($customerName, $street, $city, $postcode, $country);
    }

    #[When('I select :shippingMethodName as my shipping method')]
    public function iSelectMyShippingMethod(string $shippingMethodName): void
    {
        $this->onePageCheckoutPage->selectShippingMethod($shippingMethodName);
    }

    #[When('I select :paymentMethodName as my payment method')]
    public function iSelectMyPaymentMethod(string $paymentMethodName): void
    {
        $this->onePageCheckoutPage->selectPaymentMethod($paymentMethodName);
    }

    #[When('I apply the :couponCode coupon')]
    public function iApplyTheCoupon(string $couponCode): void
    {
        $this->onePageCheckoutPage->applyCoupon($couponCode);
    }

    #[When('I remove my coupon')]
    public function iRemoveTheCoupon(): void
    {
        $this->onePageCheckoutPage->removeCoupon();
    }

    #[When('I place my order')]
    public function iPlaceMyOrder(): void
    {
        $this->onePageCheckoutPage->pay();
    }

    #[Then('I should be on the one page checkout')]
    #[Then('I should be redirected to the one page checkout')]
    public function iShouldBeOnTheOnePageCheckout(): void
    {
        Assert::true($this->onePageCheckoutPage->isOpen());
    }

    #[Then('I should no longer be on the one page checkout')]
    public function iShouldNoLongerBeOnTheOnePageCheckout(): void
    {
        Assert::false($this->onePageCheckoutPage->isOpen());
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

    #[Then('there should be no shipping methods available')]
    public function thereShouldBeNoShippingMethodsAvailable(): void
    {
        Assert::true($this->onePageCheckoutPage->hasNoShippingMethodsMessage());
    }

    #[Then('the :shippingMethodName shipping method should be selected')]
    public function theShippingMethodShouldBeSelected(string $shippingMethodName): void
    {
        Assert::true($this->onePageCheckoutPage->hasSelectedShippingMethod($shippingMethodName));
    }

    #[Then('my order should be placed')]
    public function myOrderShouldBePlaced(): void
    {
        $order = $this->getPlacedOrder();
        Assert::same($order->getCheckoutState(), OrderCheckoutStates::STATE_COMPLETED);
    }

    #[Then('I should be notified that the order summary has changed')]
    public function iShouldBeNotifiedThatTheOrderSummaryHasChanged(): void
    {
        Assert::true($this->onePageCheckoutPage->hasFlashMessage('The order summary has changed'));
    }

    #[Then('I should be notified that the coupon code is invalid')]
    public function iShouldBeNotifiedThatTheCouponCodeIsInvalid(): void
    {
        Assert::true($this->onePageCheckoutPage->hasValidationError('Coupon code is invalid'));
    }

    #[Then('my order should not be placed')]
    public function myOrderShouldNotBePlaced(): void
    {
        $order = $this->getPlacedOrder();
        Assert::notSame($order->getCheckoutState(), OrderCheckoutStates::STATE_COMPLETED);
    }

    #[Then('my order should be billed to :street')]
    public function myOrderShouldBeBilledTo(string $street): void
    {
        $order = $this->getPlacedOrder();

        $billingAddress = $order->getBillingAddress();

        Assert::notNull($billingAddress);
        Assert::same($billingAddress->getStreet(), $street);
    }

    #[Then('my order should be shipped to :street')]
    public function myOrderShouldBeShippedTo(string $street): void
    {
        $order = $this->getPlacedOrder();

        $shippingAddress = $order->getShippingAddress();

        Assert::notNull($shippingAddress);
        Assert::same($shippingAddress->getStreet(), $street);
    }

    #[Then('I should be given the promotion discount')]
    public function iShouldBeGivenThePromotionDiscount(): void
    {
        Assert::true($this->onePageCheckoutPage->hasPromotionDiscount());
    }

    #[Then('I should not be given the promotion discount')]
    public function iShouldNotBeGivenThePromotionDiscount(): void
    {
        Assert::false($this->onePageCheckoutPage->hasPromotionDiscount());
    }

    #[Then('the :couponCode coupon should be used')]
    public function theCouponShouldBeUsed(string $couponCode): void
    {
        $this->orderManager->clear();

        $coupon = $this->couponRepository->findOneBy(['code' => $couponCode]);

        Assert::isInstanceOf($coupon, PromotionCouponInterface::class, 'Coupon was not found.');
        Assert::same($coupon->getUsed(), 1);
    }

    private function getPlacedOrder(): OrderInterface
    {
        $cart = $this->sharedStorage->get('order');
        Assert::isInstanceOf($cart, OrderInterface::class);

        $orderId = $cart->getId();
        $this->orderManager->clear();

        $order = $this->orderRepository->find($orderId);
        Assert::isInstanceOf($order, OrderInterface::class, 'Order was not found.');

        return $order;
    }
}
