@one_page_checkout_shop_checkout
Feature: Redirecting the standard checkout to the one page checkout
    In order to complete my purchase on a single page
    As a Customer
    I want to be redirected to the one page checkout on channels that use it

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And I added product "PHP T-Shirt" to the cart

    @ui @no-api
    Scenario: Being redirected to the one page checkout when it is enabled
        Given one page checkout is enabled for this channel
        When I try to open checkout addressing page
        Then I should be on the one page checkout

    @ui @no-api
    Scenario: Following the standard checkout when one page checkout is disabled
        When I try to open checkout addressing page
        Then I should be on the checkout addressing step
