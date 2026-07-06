@one_page_checkout_shop_checkout
Feature: Applying a promotion coupon on the one page checkout
    In order to pay less for my order
    As a Customer
    I want to apply a promotion coupon on the one page checkout

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store has "UPS" shipping method with "$5.00" fee
        And the store allows paying "Offline"
        And the store has promotion "Christmas sale" with coupon "SANTA2024"
        And this promotion gives "$5.00" discount to every order
        And one page checkout is enabled for this channel

    @ui @no-api @javascript
    Scenario: Being given a discount after applying a coupon
        Given I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I apply the "SANTA2024" coupon
        Then I should be given the promotion discount

    @ui @no-api @javascript
    Scenario: Losing the discount after removing an applied coupon
        Given I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I apply the "SANTA2024" coupon
        And I remove my coupon
        Then I should not be given the promotion discount

    @ui @no-api @javascript
    Scenario: Placing an order with an applied coupon marks the coupon as used
        Given I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I apply the "SANTA2024" coupon
        And I provide my email as "john.doe@example.com"
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        And I select "UPS" as my shipping method
        And I select "Offline" as my payment method
        And I place my order
        Then my order should be placed
        And the "SANTA2024" coupon should be used
