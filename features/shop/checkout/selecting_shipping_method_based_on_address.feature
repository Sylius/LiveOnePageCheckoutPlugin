@one_page_checkout_shop_checkout
Feature: Selecting a shipping method on the one page checkout based on the shipping address
    In order to ship my order to my address
    As a Customer
    I want to be offered only the shipping methods available for my shipping country

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And there is a zone "The Rest of the World" containing all other countries
        And the store has "UPS" shipping method with "$5.00" fee within the "US" zone
        And the store has "FedEx World" shipping method with "$25.00" fee for the rest of the world
        And the store allows paying "Offline"
        And one page checkout is enabled for this channel

    @ui @no-api @javascript
    Scenario: Being offered the shipping method available for my shipping country as a guest
        Given I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I provide my email as "john.doe@example.com"
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        Then the "UPS" shipping method should be available
        And the "FedEx World" shipping method should not be available

    @ui @no-api @javascript
    Scenario: Being offered the shipping method available for my default address as a logged in customer
        Given I am a logged in customer
        And I have an address "John Doe", "Main St. 12", "90210", "Los Angeles", "United States", "Arkansas" in my address book
        And my default address is of "John Doe"
        And I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        Then the "UPS" shipping method should be available
        And the "UPS" shipping method should be selected

    @ui @no-api @javascript
    Scenario: Placing an order through the one page checkout as a guest
        Given I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I provide my email as "john.doe@example.com"
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        And I select "UPS" as my shipping method
        And I select "Offline" as my payment method
        And I place my order
        Then I should no longer be on the one page checkout
        And my order should be placed
