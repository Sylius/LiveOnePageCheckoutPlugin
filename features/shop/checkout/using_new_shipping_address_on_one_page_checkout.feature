@one_page_checkout_shop_checkout
Feature: Using a new shipping address on the one page checkout as a logged in customer
    In order to ship my order somewhere else than my saved address
    As a logged in Customer
    I want to provide a new shipping address on the one page checkout

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store has "UPS" shipping method with "$5.00" fee
        And the store allows paying "Offline"
        And one page checkout is enabled for this channel

    @ui @no-api @javascript
    Scenario: Placing an order shipped to a new address instead of the saved one
        Given I am a logged in customer
        And I have an address "John Doe", "Main St. 12", "90210", "Los Angeles", "United States", "Arkansas" in my address book
        And my default address is of "John Doe"
        And I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I want to use a new shipping address
        And I provide my shipping address as "New St. 5", "Chicago", "60601", "United States" for "John Doe"
        And I select "UPS" as my shipping method
        And I select "Offline" as my payment method
        And I place my order
        Then my order should be placed
        And my order should be shipped to "New St. 5"
