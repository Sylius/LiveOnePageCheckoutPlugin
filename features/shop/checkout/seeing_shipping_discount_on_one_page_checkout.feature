@one_page_checkout_shop_checkout
Feature: Seeing a shipping discount on the one page checkout
    In order to know I am paying less for shipping
    As a Customer
    I want to see the discounted shipping cost on the one page checkout

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store has "UPS" shipping method with "$10.00" fee
        And the store allows paying "Offline"
        And one page checkout is enabled for this channel

    @ui @no-api @javascript
    Scenario: Seeing the discounted shipping cost after selecting a shipping method
        Given there is a promotion "50% shipping discount"
        And it gives "50%" discount on shipping to every order
        And I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I provide my email as "john.doe@example.com"
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        And I select "UPS" as my shipping method
        Then I should be given a shipping discount

    @ui @no-api @javascript
    Scenario: Not seeing a shipping discount without an applicable shipping promotion
        Given I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I provide my email as "john.doe@example.com"
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        And I select "UPS" as my shipping method
        Then I should not be given a shipping discount
