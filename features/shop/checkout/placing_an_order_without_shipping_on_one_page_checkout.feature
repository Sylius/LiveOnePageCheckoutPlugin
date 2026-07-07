@one_page_checkout_shop_checkout
Feature: Placing an order that does not require shipping on the one page checkout
    In order to buy a product that is not shipped
    As a Customer
    I want to place an order without providing a shipping method

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "E-Book" priced at "$9.99"
        And this product does not require shipping
        And the store allows paying "Offline"
        And one page checkout is enabled for this channel

    @ui @no-api @javascript
    Scenario: Placing an order for a non-shippable product as a guest
        Given I added product "E-Book" to the cart
        When I open the one page checkout
        And I provide my email as "john.doe@example.com"
        And I provide my billing address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        And I select "Offline" as my payment method
        And I place my order
        Then my order should be placed
        And my order should be billed to "Main St. 12"
