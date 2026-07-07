@one_page_checkout_shop_checkout
Feature: Providing a different billing address on the one page checkout
    In order to be billed at a different address than the shipping one
    As a Customer
    I want to provide a separate billing address on the one page checkout

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store has "UPS" shipping method with "$5.00" fee
        And the store allows paying "Offline"
        And one page checkout is enabled for this channel

    @ui @no-api @javascript
    Scenario: Placing an order with a different billing address as a guest
        Given I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I provide my email as "john.doe@example.com"
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        And I use a different billing address
        And I provide my billing address as "Billing Ave. 7", "Boston", "02101", "United States" for "Jane Doe"
        And I select "UPS" as my shipping method
        And I select "Offline" as my payment method
        And I place my order
        Then my order should be placed
        And my order should be billed to "Billing Ave. 7"

    @ui @no-api @javascript
    Scenario: Placing an order with a new billing address as a logged in customer
        Given I am a logged in customer
        And I have an address "John Doe", "Main St. 12", "90210", "Los Angeles", "United States", "Arkansas" in my address book
        And my default address is of "John Doe"
        And I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I want to use a new shipping address
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        And I use a different billing address
        And I want to use a new billing address
        And I provide my billing address as "Billing Ave. 7", "Boston", "02101", "United States" for "Jane Doe"
        And I select "UPS" as my shipping method
        And I select "Offline" as my payment method
        And I place my order
        Then my order should be placed
        And my order should be billed to "Billing Ave. 7"
