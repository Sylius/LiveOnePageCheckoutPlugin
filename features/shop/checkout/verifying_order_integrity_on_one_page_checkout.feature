@one_page_checkout_shop_checkout
Feature: Verifying the order integrity on the one page checkout
    In order to always pay the amount I agreed to
    As a Customer
    I want to be warned when my order summary changes before I place the order

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store has "UPS" shipping method with "$5.00" fee
        And the store allows paying "Offline"
        And one page checkout is enabled for this channel

    @ui @no-api @javascript
    Scenario: Being warned when the product price changes before placing the order
        Given I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I provide my email as "john.doe@example.com"
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        And I select "UPS" as my shipping method
        And I select "Offline" as my payment method
        And the product "PHP T-Shirt" changed its price to "$9.99"
        And I place my order
        Then I should be notified that the order summary has changed
        And my order should not be placed

    @ui @no-api @javascript
    Scenario: Being warned when the applied coupon stops working before placing the order
        Given the store has promotion "Christmas sale" with coupon "SANTA2024"
        And this promotion gives "$5.00" discount to every order
        And I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I apply the "SANTA2024" coupon
        And I provide my email as "john.doe@example.com"
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        And I select "UPS" as my shipping method
        And I select "Offline" as my payment method
        And the promotion was disabled for the channel "United States"
        And I place my order
        Then I should be notified that the coupon code is invalid
        And my order should not be placed
