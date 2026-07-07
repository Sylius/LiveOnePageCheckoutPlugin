@one_page_checkout_shop_checkout
Feature: Seeing no available shipping methods on the one page checkout
    In order to know that I cannot ship my order to the provided address
    As a Customer
    I want to be informed when no shipping method is available

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store allows paying "Offline"
        And one page checkout is enabled for this channel

    @ui @no-api @javascript
    Scenario: Being informed that no shipping method is available for my address
        Given there is a zone "The Rest of the World" containing all other countries
        And the store has "World Post" shipping method with "$25.00" fee for the rest of the world
        And I added product "PHP T-Shirt" to the cart
        When I open the one page checkout
        And I provide my shipping address as "Main St. 12", "New York", "10001", "United States" for "John Doe"
        Then there should be no shipping methods available
